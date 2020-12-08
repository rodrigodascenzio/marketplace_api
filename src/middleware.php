<?php
// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);

$container = $app->getContainer();
$settings = $container->get('settings');

$app->add(new Tuupola\Middleware\JwtAuthentication([
	"secure" => true,
	"path" => "", /* or ["/api", "/admin"] */
	"ignore" => ["/v1/legal","/v1/signup", "/v1/signin", "/v1/boleto/update","/v1/cron","/v1/sendemail","/v1/verifycodeemail","/v1/sendsms","/v1/verifycodephonenumber", "/v1/tempNotification",
    "/v2/legal","/v2/signup", "/v2/signin", "/v2/boleto/update","/v2/cron","/v2/sendemail","/v2/verifycodeemail","/v2/sendsms","/v2/verifycodephonenumber", "/v2/tempNotification", "/v2/company/tempNotification",
     "/v3/legal","/v3/signup", "/v3/signin", "/v3/boleto/update","/v3/cron","/v3/sendemail","/v3/verifycodeemail","/v3/sendsms","/v3/verifycodephonenumber", "/v3/tempNotification", "/v3/company/tempNotification",
     "/v4/legal","/v4/signup", "/v4/signin", "/v4/boleto/update","/v4/cron","/v4/sendemail","/v4/verifycodeemail","/v4/sendsms","/v4/verifycodephonenumber", "/v4/tempNotification", "/v4/company/tempNotification","/v4/product/detail","/v4/product/user","/v4/company/checkDomain"],
    "secret" => $settings['jwt']['secret'],
    "before" => function ($request, $arguments) {
        return $request->withAttribute("jwt",$arguments["token"]);
    },
    "error" => function ($response, $arguments) {
        $data["status"] = "error";
        $data["message"] = $arguments["message"];
        return $response
            ->withHeader("Content-Type", "application/json")
            ->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
]));

$app->add(new Tuupola\Middleware\CorsMiddleware([
    "origin" => ["*"],
    "methods" => ["GET", "POST", "PATCH", "DELETE", "OPTIONS"],    
    "headers.allow" => ["Origin", "Content-Type", "Authorization", "Accept", "ignoreLoadingBar", "X-Requested-With", "Access-Control-Allow-Origin"],
    "headers.expose" => [],
    "credentials" => true,
    "cache" => 0,        
]));

