
# Startup Application on local machine

Deploy platform services such as MongoDB, Redis, ElasticSearch to k8s cluster,
```
kubectl apply -k shared-services/overlays/local
```

Deploy application microservices to K8s cluster on Docker desktop (Windows) or another K8S cluster,
```
kubectl apply -k apps/overlays/local
```