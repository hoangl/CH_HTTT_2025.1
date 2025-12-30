// src/theme.ts
import { createTheme } from '@mui/material/styles';

const theme = createTheme({
  palette: {
    primary: {
      main: '#4f46e5', // Màu Indigo hiện đại thay vì xanh blue mặc định
    },
    secondary: {
      main: '#f43f5e', // Màu Rose cho điểm nhấn
    },
    background: {
      default: '#f8fafc', // Màu nền xám xanh nhẹ (Slate 50)
      paper: '#ffffff',
    },
    text: {
      primary: '#0f172a', // Slate 900
      secondary: '#64748b', // Slate 500
    },
  },
  typography: {
    fontFamily: '"Inter", "Roboto", "Helvetica", "Arial", sans-serif',
    h1: { fontWeight: 700 },
    h2: { fontWeight: 700 },
    h3: { fontWeight: 600 },
    button: { textTransform: 'none', fontWeight: 600 }, // Bỏ viết hoa mặc định của nút bấm
  },
  components: {
    MuiAppBar: {
      styleOverrides: {
        root: {
          backgroundColor: '#ffffff', // Header màu trắng
          color: '#0f172a', // Chữ header màu đen
          boxShadow: '0 1px 3px 0 rgb(0 0 0 / 0.1)', // Đổ bóng nhẹ
        },
      },
    },
    MuiButton: {
      styleOverrides: {
        root: {
          borderRadius: 8, // Bo tròn nút
          padding: '8px 16px',
        },
      },
    },
    MuiPaper: {
      styleOverrides: {
        root: {
          backgroundImage: 'none',
        },
        rounded: {
          borderRadius: 12, // Bo tròn các thẻ (Card)
        }
      }
    }
  },
});

export default theme;