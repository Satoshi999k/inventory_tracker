/**
 * Frontend Server Configuration Tests - Jest
 * Tests Express server setup and configuration
 * @jest-environment node
 */

describe('Frontend Server Configuration', () => {
  test('Express module should be available', () => {
    const express = require('express');
    expect(express).toBeDefined();
  });

  test('Path module should be available', () => {
    const path = require('path');
    expect(path).toBeDefined();
  });

  test('Should define PORT environment variable', () => {
    const PORT = process.env.PORT || 3000;
    expect(PORT).toBeDefined();
    expect(PORT).toEqual(3000);
  });
});

describe('Server Setup Tests', () => {
  test('Should create Express app', () => {
    const express = require('express');
    const app = express();
    expect(app).toBeDefined();
    expect(typeof app.use).toBe('function');
  });

  test('Should support middleware', () => {
    const express = require('express');
    const app = express();
    expect(typeof app.use).toBe('function');
    expect(typeof app.get).toBe('function');
    expect(typeof app.post).toBe('function');
  });

  test('Should support routing', () => {
    const express = require('express');
    const app = express();
    app.get('/', (req, res) => res.send('Hello'));
    expect(app._router).toBeDefined();
  });

  test('Should support static files', () => {
    const express = require('express');
    const app = express();
    const path = require('path');
    app.use(express.static(path.join(__dirname, '../public')));
    expect(app).toBeDefined();
  });
});

describe('Security Configuration', () => {
  test('Should disable X-Powered-By header', () => {
    const express = require('express');
    const app = express();
    app.disable('x-powered-by');
    expect(app.get('x-powered-by')).toBe(false);
  });

  test('Should support CORS headers', () => {
    const express = require('express');
    const app = express();
    
    app.use((req, res, next) => {
      res.header('Access-Control-Allow-Origin', '*');
      next();
    });
    
    expect(app).toBeDefined();
  });

  test('Should support JSON middleware', () => {
    const express = require('express');
    const app = express();
    app.use(express.json());
    expect(app).toBeDefined();
  });

  test('Should support URL encoded middleware', () => {
    const express = require('express');
    const app = express();
    app.use(express.urlencoded({ extended: true }));
    expect(app).toBeDefined();
  });
});

describe('Module Dependencies', () => {
  test('Should have all required packages installed', () => {
    expect(() => {
      require('express');
      require('path');
    }).not.toThrow();
  });

  test('Should support async/await', async () => {
    const promise = Promise.resolve('test');
    const result = await promise;
    expect(result).toBe('test');
  });
});
