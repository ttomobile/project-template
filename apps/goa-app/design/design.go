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
