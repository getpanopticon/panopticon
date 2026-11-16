# Release and Docker

## Release Process

Panopticon deviates from the generic Akeeba release-workflow recipe. See the `panopticon-release` skill before running any Phing release target.

## Docker

Docker detection via absence of `src/.not_docker` file.

When asked to build, test, or manually publish the Docker image to GHCR, read `assets/Docker Build.md` first — it contains the full step-by-step procedure including prerequisites, local testing with `docker-compose.override.yml`, tagging conventions, and multi-arch Buildx commands.
