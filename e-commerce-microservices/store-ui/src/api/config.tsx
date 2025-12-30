import axios from "axios"

const axiosClient = axios.create();

axiosClient.defaults.headers.common = {
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

//All request will wait 2 seconds before timeout
axiosClient.defaults.timeout = 2000;

export const productsUrl = "/api/products/"
export const cartUrl = "/api/cart/"

export default axiosClient