# Shopping Cart Microservice

## Prerequisites

### Java 17
Install OpenJDK 17

## Build
Set Redis server environment variables in '.env' file. This file will not checked into Git as it holds sensitive information such as Redis password.
```
gradle build
```
clean
```
gradle clean
```


## Run Locally
```
gradle bootRun
```

### Build Docker Image

Build docker image,
```
docker build -t cart:latest .
```
