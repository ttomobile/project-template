package main

import (
	"context"
	"log"
	"net/http"
	"os"
	"os/signal"
	"syscall"
	"time"

	goahttp "goa.design/goa/v3/http"
	httpmiddleware "goa.design/goa/v3/http/middleware"
	goamiddleware "goa.design/goa/v3/middleware"

	statushttp "github.com/example/project-template/apps/goa-app/gen/http/status/server"
	statusgen "github.com/example/project-template/apps/goa-app/gen/status"
	statusservice "github.com/example/project-template/apps/goa-app/status"
)

func main() {
	ctx, stop := signal.NotifyContext(context.Background(), os.Interrupt, syscall.SIGTERM)
	defer stop()

	svc := statusservice.NewService()
	endpoints := statusgen.NewEndpoints(svc)

	mux := goahttp.NewMuxer()
	dec := goahttp.RequestDecoder
	enc := goahttp.ResponseEncoder

	server := statushttp.New(endpoints, mux, dec, enc, nil, nil)

	logger := goamiddleware.NewLogger(log.New(os.Stdout, "goa-app: ", log.LstdFlags))
	server.Use(httpmiddleware.RequestID())
	server.Use(httpmiddleware.Log(logger))

	statushttp.Mount(mux, server)

	httpServer := &http.Server{
		Addr:    ":8080",
		Handler: mux,
	}

	errc := make(chan error, 1)
	go func() {
		log.Println("starting goa-app HTTP server on :8080")
		if err := httpServer.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			errc <- err
			return
		}
		close(errc)
	}()

	select {
	case <-ctx.Done():
		log.Println("shutdown signal received; initiating graceful shutdown")
	case err, ok := <-errc:
		if ok && err != nil {
			log.Fatalf("server failed: %v", err)
		}
		return
	}

	shutdownCtx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()

	if err := httpServer.Shutdown(shutdownCtx); err != nil {
		log.Fatalf("graceful shutdown failed: %v", err)
	}

	if err, ok := <-errc; ok && err != nil {
		log.Fatalf("server failed during shutdown: %v", err)
	}

	log.Println("server shutdown completed")
}
