# Outbound HTTP

**Always** get a Guzzle client from `$container->httpFactory->makeClient()`. Never write `new Client()`. The factory pushes the forbidden-IP-ranges middleware (GHSA-6234-p3mh-7j3x) onto the handler stack, so every call site inherits SSRF protection for free; a hand-built client has none.

- Need a custom handler stack? Pass it as the `$stack` **parameter**. Passing a `handler` key in `$clientOptions` replaces the whole stack, drops the guard, and now throws a `LogicException`.
- `tests/Unit/Architecture/NoDirectGuzzleClientTest.php` enforces this.
- Writing a non-HTTP outbound connection (raw socket, another protocol) that no factory can wrap? Call `ForbiddenIpRanges::fromConfig($container)->isForbiddenHost($host)` by hand before connecting, and say why in a comment. `Site::getCertificateInformation()` is the worked example.

