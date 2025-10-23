package design

import (
        . "goa.design/goa/v3/dsl"
)

var _ = API("goa-app", func() {
	Title("Project Template Goa Service")
	Description("A minimal Goa service setup for the project template repository.")
	Server("goa-app", func() {
		Host("localhost", func() {
			URI("http://localhost:8080")
		})
	})
})

var _ = Service("status", func() {
        Description("Provides service status information.")

	Method("show", func() {
		Description("Returns a simple service status message.")

		Result(String, func() {
			Example("up")
		})

		HTTP(func() {
			GET("/status")
			Response(StatusOK)
		})
	})
})

var StartResult = ResultType("application/vnd.oidc-start+json", func() {
        Description("Authorization initiation payload.")
        TypeName("StartResponse")
        Attributes(func() {
                Attribute("auth_url", String, "Authorization endpoint URL with query parameters.")
                Attribute("state", String, "Opaque state value to correlate the login flow.")
                Required("auth_url", "state")
        })
        View("default", func() {
                Attribute("auth_url")
                Attribute("state")
        })
})

var SessionResult = ResultType("application/vnd.oidc-session+json", func() {
        Description("Completed session containing tokens and user information.")
        TypeName("SessionResponse")
        Attributes(func() {
                Attribute("access_token", String, "Opaque access token issued by the provider.")
                Attribute("id_token", String, "ID token in JWT format.")
                Attribute("expires_in", Int, "Seconds until the access token expires.")
                Attribute("scope", String, "Granted scopes.")
                Attribute("user", UserInfo)
                Required("access_token", "id_token", "expires_in", "scope", "user")
        })
        View("default", func() {
                Attribute("access_token")
                Attribute("id_token")
                Attribute("expires_in")
                Attribute("scope")
                Attribute("user")
        })
})

var UserInfo = Type("UserInfo", func() {
        Description("Subset of user attributes returned by the provider.")
        Attribute("sub", String, "Subject identifier")
        Attribute("email", String, "Email address")
        Attribute("name", String, "Display name")
        Required("sub", "email")
})

var CompletePayload = Type("CompletePayload", func() {
        Description("Parameters returned to the client after authorization.")
        Attribute("state", String, "State originally issued in the authorization request")
        Attribute("code", String, "Authorization code returned by the provider")
        Required("state", "code")
})

var _ = Service("oidc", func() {
        Description("OIDC login orchestration endpoints for the Goa sample client.")

        Method("start", func() {
                Description("Create a PKCE authorization request and return the redirect URL.")
                Result(StartResult)
                HTTP(func() {
                        POST("/auth/start")
                        Response(StatusOK)
                })
        })

        Method("complete", func() {
                Description("Exchange an authorization code for tokens.")
                Payload(CompletePayload)
                Result(SessionResult)
                Error("not_found", String, "State could not be matched to a login attempt.")
                HTTP(func() {
                        POST("/auth/callback")
                        Response(StatusOK)
                        Response("not_found", StatusNotFound)
                })
        })

        Method("session", func() {
                Description("Retrieve the session associated with the provided state.")
                Payload(func() {
                        Attribute("state", String, "State identifier")
                        Required("state")
                })
                Result(SessionResult)
                Error("not_found", String)
                HTTP(func() {
                        GET("/sessions/{state}")
                        Response(StatusOK)
                        Response("not_found", StatusNotFound)
                })
        })
})
