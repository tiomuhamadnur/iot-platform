FROM golang:1.25-alpine AS build

WORKDIR /app

COPY go.mod go.sum ./
RUN go mod download

COPY . .
RUN go build -o /bin/ingestion ./cmd/ingestion

FROM alpine:3.20

WORKDIR /app
RUN apk add --no-cache ca-certificates

COPY --from=build /bin/ingestion /usr/local/bin/ingestion

CMD ["ingestion"]
