# Quick Upload Real-Time Preview Test

## Test Confirmation Codes

Use these test codes to see the real-time preview functionality:

### Test Code: 123456
- **Items**: Traditional Coffee Cup Set (2x), Decorative Vase (1x)
- **Total**: 1,300.00 ETB
- **Preview**: Shows 2 items with images and quantities

### Test Code: 654321
- **Items**: Ceramic Dinner Plate (4x)
- **Total**: 320.00 ETB
- **Preview**: Shows single item order

### Test Code: 111111
- **Items**: Handmade Bowl Set (3x), Clay Water Jug (1x), Pottery Mug (2x)
- **Total**: 1,280.00 ETB
- **Preview**: Shows 3 different items (largest test order)

### Invalid Code: 999999
- **Result**: Shows "Invalid confirmation code" message
- **Preview**: No order details displayed

## How to Test

1. Go to: `http://localhost/ashreka-pottery-system advanced/views/customer/quick_upload.php`
2. Enter one of the test codes above in the "Confirmation Code" field
3. Watch the order details appear automatically as you type the 6th digit
4. Click "View Details" button to see the full modal with larger images
5. Test the "Next" button to proceed to receipt upload step

## Expected Behavior

- Order details should appear immediately when 6 digits are entered
- Product images should display with fallback to default image
- Total amount should be formatted with commas
- "Next" button should become enabled only when valid code is entered
- Invalid codes should hide the order details and disable the "Next" button

## Files Modified

- `api/test_order_details.php` - Test API endpoint
- `views/customer/quick_upload.php` - Updated to use test endpoint