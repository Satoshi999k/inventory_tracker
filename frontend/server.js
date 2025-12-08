const express = require('express');
const path = require('path');

const app = express();
const PORT = 3000;

// Middleware
app.use(express.static(path.join(__dirname, 'public')));
app.use(express.json());

// Routes
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

app.get('/products', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'products.html'));
});

app.get('/inventory', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'inventory.html'));
});

app.get('/sales', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'sales.html'));
});

// Start server
app.listen(PORT, '0.0.0.0', () => {
    console.log(`Admin Dashboard running at http://0.0.0.0:${PORT}`);
});
