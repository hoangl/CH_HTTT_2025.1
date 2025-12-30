# Web Store Front UI
A web front end for e-commerce application. Built using React, MUI.

### Node 18
```
nvm install 18
nvm use 18.20.8
```

## Install Dependencies
Install required node modules.
```
npm install
```

### Run App Locally
`npm start`

Runs the app in the development mode. Open [http://localhost:3000](http://localhost:3000) to view it in the browser.

### Production Build
```
npm run build
```
Builds the app for production to the `build` folder.\
It correctly bundles React in production mode and optimizes the build for the best performance.

### Build Docker Image
```
docker build -t store-ui:latest .
```
