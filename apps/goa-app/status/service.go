package status

import "context"

// Service implements the status service domain logic.
type Service struct{}

// NewService constructs a new status service implementation.
func NewService() *Service {
	return &Service{}
}

// Show returns a static status string indicating the service is running.
func (s *Service) Show(ctx context.Context) (string, error) {
	return "up", nil
}
