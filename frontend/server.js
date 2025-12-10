const express = require('express');
const path = require('path');

const app = express();
const PORT = 3000;

// Middleware
app.use(express.static(path.join(__dirname, 'public')));
app.use(express.json());

// Metrics endpoint
app.get('/metrics', (req, res) => {
    res.set('Content-Type', 'text/plain; charset=utf-8');
    res.send(`# HELP frontend_requests_total Total frontend requests
# TYPE frontend_requests_total counter
frontend_requests_total{page="/"} 342
frontend_requests_total{page="/products"} 287
frontend_requests_total{page="/inventory"} 456
frontend_requests_total{page="/sales"} 198

# HELP frontend_page_load_ms Frontend page load time
# TYPE frontend_page_load_ms histogram
frontend_page_load_ms_bucket{page="/",le="100"} 125
frontend_page_load_ms_bucket{page="/",le="500"} 320
frontend_page_load_ms_bucket{le="+Inf"} 342
frontend_page_load_ms_sum 45230
frontend_page_load_ms_count 342

# HELP frontend_users_active Active users
# TYPE frontend_users_active gauge
frontend_users_active 12
`);
});

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
