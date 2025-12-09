# Product Edit Feature - Visual Guide

## Product Card with Edit Button

```
┌──────────────────────────┐
│    [Product Image]       │
├──────────────────────────┤
│  Product Name            │
│  SKU: DELL-XPS13         │
│  Category: Laptops       │
│  ₱45,000.00              │
│  Detailed description... │
│  Threshold: 5            │
│                          │
│ [Edit] [Delete Button]   │  ← New Edit Button
└──────────────────────────┘
```

## Edit Modal Interface

```
╔════════════════════════════════════════╗
║ ✎ Edit Product                    [×]  ║
╠════════════════════════════════════════╣
║                                        ║
║  SKU: DELL-XPS13                       ║  (Disabled)
║  Product Name: [Dell XPS 13      ]    ║
║  Category: [Laptops           ]        ║
║  Price: [45000.00             ]        ║
║  Cost: [30000.00              ]        ║
║  Description:                          ║
║  [                                    ]║
║  [                                    ]║
║                                        ║
║  Change Product Image:                 ║
║  [Choose File] (optional)              ║
║                                        ║
║  [Current Product Image Preview]       ║
║                                        ║
║  Stock Threshold: [5                ] ║
║                                        ║
║              [💾 Update Product]       ║
║                                        ║
╚════════════════════════════════════════╝
```

## Workflow Diagram

```
User on Products Page
        ↓
    Click Edit Icon (Orange Pencil)
        ↓
    Edit Modal Opens with Product Data
        ↓
    User Modifies Fields
    (SKU field is disabled)
        ↓
    User Can:
    ├─ Change Image (Optional)
    ├─ Update Text Fields
    └─ Modify Thresholds
        ↓
    Click "Update Product"
        ↓
    Loading State Shows "Updating..."
    (Form disabled to prevent duplicates)
        ↓
    Request Sent to Server
    (Image compressed if needed)
        ↓
    ✓ Success Message Shown
        ↓
    Modal Closes
        ↓
    Product List Updates Instantly
    (No full page reload)
```

## Button Styling Reference

### Edit Button (Orange/Warning)
- Color: #ff9800 (Orange)
- Hover: #e68900 (Darker Orange)
- Icon: Material Icons "edit"
- Position: Left button in product-actions row

### Delete Button (Red/Danger)
- Color: #f44336 (Red)
- Position: Right button in product-actions row

### Action Buttons Layout
```
┌─────────────────────────┐
│ [Edit Button] [Delete]  │  ← Flex layout, equal width
└─────────────────────────┘
```

## Form Field States

### SKU Field
- Status: DISABLED (Cannot be changed)
- Background: Light gray (#f5f5f5)
- Cursor: Not allowed
- Reason: Primary identifier for product

### Other Fields
- Status: ENABLED (Can be edited)
- Required: Name, Price, SKU (read-only)
- Optional: Category, Cost, Description, Image

## Modal Close Actions

User can close the edit modal by:
1. **Click the [×] button** in top-right corner
2. **Click outside the modal** (on gray background)
3. **Press Escape key** (if implemented)
4. **Save successful** → Modal auto-closes
5. **Error occurs** → Modal stays open to fix and retry

## Success/Error Feedback

### Success Case
```
✓ Product updated successfully
(Green banner appears at top)
Modal closes automatically
Product list refreshes
```

### Error Case
```
✗ Failed to update product: [error message]
(Red banner appears at top)
Modal stays open for user to fix and retry
```

## Image Update Flow

```
User Clicks "Change Product Image"
        ↓
    File Browser Opens
        ↓
    User Selects Image File
        ↓
    Preview Shows Selected Image
    File Info: "filename.jpg (250 KB)"
        ↓
    User Clicks "Update Product"
        ↓
    Frontend: Compress image if > 500KB
    ├─ Max dimensions: 800px
    ├─ Quality: 70%
    └─ Result: ~50-150KB
        ↓
    Send to Backend
        ↓
    Backend: Save to /uploads/products/
    Database: Store file path
        ↓
    ✓ Success
```

## Mobile Responsive Design

### Desktop (> 768px)
- Modal centered on screen
- Full width form fields
- Two buttons side-by-side

### Tablet/Mobile (≤ 768px)
- Modal takes up most of screen
- Form fields full width
- Buttons stack or side-by-side based on space
- Touch-friendly button size

## Keyboard Interactions

| Key | Action |
|-----|--------|
| Tab | Move between form fields |
| Enter | Submit form (when on button) |
| Escape | Close modal (if enabled) |
| Space | Toggle checkboxes/buttons |

## Accessibility Features

- Proper form labels with `<label>` elements
- Disabled SKU field clearly indicated
- Error messages shown in user-readable format
- Color contrast meets WCAG standards
- Keyboard navigation fully supported
- Screen reader friendly (semantic HTML)
