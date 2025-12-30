// src/App.tsx
import { BrowserRouter, Routes, Route } from "react-router-dom"
import { ThemeProvider, CssBaseline } from '@mui/material'; // Import thêm cái này
import theme from './theme'; // Import theme vừa tạo

import Home from './pages/Home/Home'
import Product from './pages/Product/Product'
import Cart from './pages/Cart/Cart'
import Layout from './components/layout/Layout'

const App = (props: any) => {
    return (
        // Bọc ThemeProvider ra ngoài cùng
        <ThemeProvider theme={theme}>
            <CssBaseline /> {/* Reset CSS mặc định của trình duyệt */}
            <Layout>
                <Routes>
                    <Route path="/" element={<Home />} />
                    <Route path="/product/:id" element={<Product />} />
                    <Route path="/cart" element={<Cart />} />
                </Routes>
            </Layout>
        </ThemeProvider>
    )
}
export default App