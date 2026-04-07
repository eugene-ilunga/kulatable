# Sales Report Template - Error Code 10 Fix

## Error Message
```
(#10) Application does not have permission for this action
```

## Root Cause
The `sales_report` template in Meta Business Manager is **not configured with DOCUMENT format** in the header. The code is trying to send a PDF document, but the template doesn't allow it.

## Solution: Update Meta Template

### Step 1: Access Meta Business Manager
1. Go to [Meta Business Manager](https://business.facebook.com)
2. Navigate to **WhatsApp Manager** → **Message Templates**
3. Find the `sales_report` template

### Step 2: Edit Template Header
1. Click on the `sales_report` template
2. Click **"Edit"** or **"Create New Version"**
3. In the **Header** section:
   - **Change format from TEXT to DOCUMENT**
   - Remove any text variables (if present)
   - The document will be attached dynamically by the code

### Step 3: Verify Template Structure
The template should have:

**Header:**
- Format: **DOCUMENT** (not TEXT)
- No variables needed (document added by code)

**Body:**
- 6 variables:
  - `{{1}}` - Period type (Daily/Weekly/Monthly)
  - `{{2}}` - Period (Date/Date Range/Month)
  - `{{3}}` - Total orders
  - `{{4}}` - Total revenue
  - `{{5}}` - Net revenue
  - `{{6}}` - Tax and Discount (combined)

**Footer:**
- Static text: "Generated automatically"

**Button:**
- Type: URL
- Text: "View Report"
- URL: `https://yourdomain.com/reports/sales-report` (static, no variables)

### Step 4: Submit for Approval
1. Review all components
2. Click **"Submit"** for Meta approval
3. Wait for approval (usually 24-48 hours)

## Why This Error Occurs

Error code 10 means:
- The template structure doesn't match what the code is sending
- The template doesn't have permission for document media
- The template needs to be re-approved after format changes

## Verification

After template is approved, test by running:
```bash
php artisan whatsapp:process-report-schedules
```

The message should send successfully with:
- ✅ PDF document attached in header
- ✅ Body variables populated
- ✅ "View Report" button working

## Reference: Operations Summary Template

The `operations_summary` template works because it has:
- Header: DOCUMENT format (configured in Meta)
- Body: Variables
- Footer: Static text
- Button: Static URL

The `sales_report` template needs the **same header format** to work.

