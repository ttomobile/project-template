package oidc

import (
	"context"
	"crypto/rand"
	"crypto/sha256"
	"encoding/base64"
	"encoding/json"
	"errors"
	"fmt"
	"net/http"
	"net/url"
	"os"
	"strings"
	"sync"
	"time"

	oidcgen "github.com/example/project-template/apps/goa-app/gen/oidc"
	"github.com/google/uuid"
)

// Service implements the OIDC client orchestration logic.
type Service struct {
	cfg        Config
	metadata   metadata
	httpClient *http.Client

	mu       sync.Mutex
	pending  map[string]pendingLogin
	sessions map[string]*oidcgen.SessionResponse
}

// Config contains the client configuration used for all requests.
type Config struct {
	ProviderURL  string
	ClientID     string
	ClientSecret string
	RedirectURI  string
	Scope        string
	Timeout      time.Duration
}

type metadata struct {
	authorizationEndpoint string
	tokenEndpoint         string
	userinfoEndpoint      string
}

type pendingLogin struct {
	codeVerifier string
	createdAt    time.Time
}

// NewService constructs a service using environment variables for configuration.
func NewService() *Service {
	cfg := Config{
		ProviderURL:  getEnv("OIDC_PROVIDER_URL", "http://localhost:8000"),
		ClientID:     getEnv("GOA_OIDC_CLIENT_ID", "goa-client"),
		ClientSecret: os.Getenv("GOA_OIDC_CLIENT_SECRET"),
		RedirectURI:  getEnv("GOA_OIDC_REDIRECT_URI", "http://localhost:3000/callback?source=goa"),
		Scope:        getEnv("GOA_OIDC_SCOPE", "openid profile email"),
		Timeout:      10 * time.Second,
	}

	svc := &Service{
		cfg:        cfg,
		httpClient: &http.Client{Timeout: cfg.Timeout},
		pending:    make(map[string]pendingLogin),
		sessions:   make(map[string]*oidcgen.SessionResponse),
	}

	svc.loadMetadata()

	return svc
}

// Start creates an authorization request URL and stores the PKCE verifier for the
// provided state.
func (s *Service) Start(ctx context.Context) (*oidcgen.StartResponse, error) {
	state := uuid.NewString()
	verifier, challenge, err := generatePKCE()
	if err != nil {
		return nil, err
	}

	authURL, err := s.authorizationURL(state, challenge)
	if err != nil {
		return nil, err
	}

	s.mu.Lock()
	s.pending[state] = pendingLogin{codeVerifier: verifier, createdAt: time.Now()}
	s.mu.Unlock()

	return &oidcgen.StartResponse{AuthURL: authURL, State: state}, nil
}

// Complete exchanges the authorization code for tokens and stores the resulting
// session for later retrieval.
func (s *Service) Complete(ctx context.Context, payload *oidcgen.CompletePayload) (*oidcgen.SessionResponse, error) {
	if payload == nil {
		return nil, errors.New("payload is required")
	}

	s.mu.Lock()
	pending, ok := s.pending[payload.State]
	if ok {
		delete(s.pending, payload.State)
	}
	s.mu.Unlock()
	if !ok {
		return nil, oidcgen.NotFound("state not found")
	}

	tokenRes, err := s.exchangeCode(ctx, payload.Code, pending.codeVerifier)
	if err != nil {
		return nil, err
	}

	session := &oidcgen.SessionResponse{
		AccessToken: tokenRes.AccessToken,
		IDToken:     tokenRes.IDToken,
		ExpiresIn:   tokenRes.ExpiresIn,
		Scope:       tokenRes.Scope,
	}

	if info, err := s.fetchUserInfo(ctx, tokenRes.AccessToken); err == nil {
		session.User = info
	}

	s.mu.Lock()
	s.sessions[payload.State] = session
	s.mu.Unlock()

	return session, nil
}

// Session returns the previously completed session for the provided state.
func (s *Service) Session(ctx context.Context, payload *oidcgen.SessionPayload) (*oidcgen.SessionResponse, error) {
	if payload == nil {
		return nil, errors.New("payload is required")
	}

	s.mu.Lock()
	defer s.mu.Unlock()

	session, ok := s.sessions[payload.State]
	if !ok {
		return nil, oidcgen.NotFound("state not found")
	}

	return session, nil
}

func (s *Service) loadMetadata() {
	discoveryURL := fmt.Sprintf("%s/.well-known/openid-configuration", strings.TrimRight(s.cfg.ProviderURL, "/"))
	req, err := http.NewRequest(http.MethodGet, discoveryURL, nil)
	if err != nil {
		return
	}

	resp, err := s.httpClient.Do(req)
	if err != nil {
		return
	}
	defer resp.Body.Close()

	var data struct {
		AuthorizationEndpoint string `json:"authorization_endpoint"`
		TokenEndpoint         string `json:"token_endpoint"`
		UserinfoEndpoint      string `json:"userinfo_endpoint"`
	}

	if err := json.NewDecoder(resp.Body).Decode(&data); err != nil {
		return
	}

	s.metadata = metadata{
		authorizationEndpoint: data.AuthorizationEndpoint,
		tokenEndpoint:         data.TokenEndpoint,
		userinfoEndpoint:      data.UserinfoEndpoint,
	}
}

func (s *Service) authorizationURL(state, codeChallenge string) (string, error) {
	endpoint := s.metadata.authorizationEndpoint
	if endpoint == "" {
		endpoint = fmt.Sprintf("%s/oidc/authorize", strings.TrimRight(s.cfg.ProviderURL, "/"))
	}

	values := url.Values{}
	values.Set("client_id", s.cfg.ClientID)
	values.Set("redirect_uri", s.cfg.RedirectURI)
	values.Set("response_type", "code")
	values.Set("scope", s.cfg.Scope)
	values.Set("state", state)
	values.Set("code_challenge", codeChallenge)
	values.Set("code_challenge_method", "S256")

	return endpoint + "?" + values.Encode(), nil
}

type tokenResponse struct {
	AccessToken string `json:"access_token"`
	IDToken     string `json:"id_token"`
	ExpiresIn   int    `json:"expires_in"`
	Scope       string `json:"scope"`
}

func (s *Service) exchangeCode(ctx context.Context, code, verifier string) (*tokenResponse, error) {
	endpoint := s.metadata.tokenEndpoint
	if endpoint == "" {
		endpoint = fmt.Sprintf("%s/oidc/token", strings.TrimRight(s.cfg.ProviderURL, "/"))
	}

	form := url.Values{}
	form.Set("grant_type", "authorization_code")
	form.Set("code", code)
	form.Set("redirect_uri", s.cfg.RedirectURI)
	form.Set("client_id", s.cfg.ClientID)
	form.Set("code_verifier", verifier)
	if s.cfg.ClientSecret != "" {
		form.Set("client_secret", s.cfg.ClientSecret)
	}

	req, err := http.NewRequestWithContext(ctx, http.MethodPost, endpoint, strings.NewReader(form.Encode()))
	if err != nil {
		return nil, err
	}
	req.Header.Set("Content-Type", "application/x-www-form-urlencoded")

	resp, err := s.httpClient.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	if resp.StatusCode >= http.StatusBadRequest {
		return nil, fmt.Errorf("token endpoint returned %d", resp.StatusCode)
	}

	var result tokenResponse
	if err := json.NewDecoder(resp.Body).Decode(&result); err != nil {
		return nil, err
	}

	if result.Scope == "" {
		result.Scope = s.cfg.Scope
	}

	if result.ExpiresIn == 0 {
		result.ExpiresIn = 3600
	}

	return &result, nil
}

func (s *Service) fetchUserInfo(ctx context.Context, accessToken string) (*oidcgen.UserInfo, error) {
	endpoint := s.metadata.userinfoEndpoint
	if endpoint == "" {
		endpoint = fmt.Sprintf("%s/oidc/userinfo", strings.TrimRight(s.cfg.ProviderURL, "/"))
	}

	req, err := http.NewRequestWithContext(ctx, http.MethodGet, endpoint, nil)
	if err != nil {
		return nil, err
	}
	req.Header.Set("Authorization", "Bearer "+accessToken)

	resp, err := s.httpClient.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	if resp.StatusCode >= http.StatusBadRequest {
		return nil, fmt.Errorf("userinfo endpoint returned %d", resp.StatusCode)
	}

	var payload struct {
		Sub   string  `json:"sub"`
		Email string  `json:"email"`
		Name  *string `json:"name"`
	}
	if err := json.NewDecoder(resp.Body).Decode(&payload); err != nil {
		return nil, err
	}

	return &oidcgen.UserInfo{
		Sub:   payload.Sub,
		Email: payload.Email,
		Name:  payload.Name,
	}, nil
}

func generatePKCE() (string, string, error) {
	verifierBytes := make([]byte, 32)
	if _, err := rand.Read(verifierBytes); err != nil {
		return "", "", err
	}
	verifier := encodeBase64URL(verifierBytes)
	sum := sha256.Sum256([]byte(verifier))
	challenge := encodeBase64URL(sum[:])
	return verifier, challenge, nil
}

func encodeBase64URL(data []byte) string {
	return strings.TrimRight(base64.URLEncoding.EncodeToString(data), "=")
}

func getEnv(key, fallback string) string {
	if value := os.Getenv(key); value != "" {
		return value
	}
	return fallback
}
