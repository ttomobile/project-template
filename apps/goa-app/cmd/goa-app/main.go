package main

import (
	"log"
	"net/http"
	"os"

	goahttp "goa.design/goa/v3/http"
	httpmiddleware "goa.design/goa/v3/http/middleware"
	goamiddleware "goa.design/goa/v3/middleware"

	statushttp "github.com/example/project-template/apps/goa-app/gen/http/status/server"
	statusgen "github.com/example/project-template/apps/goa-app/gen/status"
	statusservice "github.com/example/project-template/apps/goa-app/status"
)

func main() {
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

	log.Println("starting goa-app HTTP server on :8080")
	if err := httpServer.ListenAndServe(); err != nil && err != http.ErrServerClosed {
		log.Fatalf("server failed: %v", err)
	}
}
