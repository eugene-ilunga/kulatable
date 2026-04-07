# WhatsApp Business API - Message Delivery Policy & Troubleshooting Guide

## Understanding "Accepted" vs "Delivered" Status

### Message Status Flow:
1. **"accepted"** ✅ - Message accepted by WhatsApp API (queued for delivery)
2. **"sent"** 📤 - Message sent to WhatsApp servers
3. **"delivered"** ✅ - Message delivered to recipient's device
4. **"read"** 👁️ - Message read by recipient
5. **"failed"** ❌ - Message failed to deliver

**Important**: "accepted" does NOT mean "delivered". It only means WhatsApp accepted your request.

---

## ⚠️ CRITICAL FINDING: Messages "Sent" But Never "Delivered"

**Your Current Situation**:
- ✅ Messages are **"accepted"** by WhatsApp API
- ✅ Messages show status **"sent"** (sent to WhatsApp servers)
- ❌ **NO messages showing as "delivered"** (not delivered to devices)
- ✅ Webhook IS receiving status updates (but only "sent", never "delivered")

**This means**: Messages are being **blocked before delivery** to recipient devices, even though:
- ✅ Numbers are in allowed recipient list
- ✅ Webhook is configured and working
- ✅ Templates are approved

---

## Common Reasons Messages Are Accepted But Not Delivered

### 1. 🔴 User Opt-In/Consent Issues (MOST LIKELY CAUSE)

**WhatsApp Policy**: Even if numbers are in allowed list, **users must have explicitly opted-in** to receive messages.

**What Meta Checks**:
- ✅ User sent you a message first (24-hour window)
- ✅ User explicitly consented during transaction
- ✅ User checked opt-in checkbox
- ❌ **Scraped/purchased phone numbers** → Messages blocked
- ❌ **No explicit consent** → Messages blocked

**How Meta Detects**:
- Tracks if user initiated conversation
- Monitors complaint/block rates
- Checks message engagement patterns

**Solution**:
1. Ensure users **explicitly opted-in** (checkbox, form, etc.)
2. Users should **send you a message first** (opens 24-hour window)
3. Document consent records
4. Avoid sending to numbers without clear opt-in

---

### 2. 🔴 Account Quality Rating Issues

**WhatsApp Policy**: Low quality rating can cause messages to be **queued but not delivered**.

**Quality Rating Factors**:
- **High (>4.5)**: Good delivery rates
- **Medium (3.5-4.5)**: May have delays
- **Low (<3.5)**: Messages blocked or heavily delayed

**What Lowers Rating**:
- High block/complaint rates
- Low message engagement
- Sending to non-opted-in users
- Spam patterns
- Promotional content without consent

**Check Rating**:
1. Go to **Meta Business Manager** → **WhatsApp** → **Phone Numbers**
2. Click your phone number
3. Check **"Quality rating"** section

**Solution**: Improve messaging practices to raise quality rating.

---

### 3. 🔴 Test Account Restrictions (MOST COMMON)

**Issue**: For **test accounts**, phone numbers MUST be in the allowed recipient list.

**Error**: `(#131030) Recipient phone number not in allowed list`

**Solution**:
1. Go to **Meta Business Manager** → **WhatsApp** → **Phone Numbers**
2. Click on your phone number
3. Go to **"Phone number settings"** → **"Manage phone number"**
4. Scroll to **"Test phone numbers"** or **"Allowed recipient list"**
5. Add recipient phone numbers (format: `919784921431` - no + sign)
6. Save changes

**Note**: Test accounts can only send to numbers in this list. Production accounts don't have this restriction.

---

### 2. 🔴 Webhook Not Configured

**Issue**: Webhook not receiving delivery status updates from Meta.

**Check**:
- Webhook URL must be publicly accessible (HTTPS)
- Webhook must be verified in Meta Business Manager
- Webhook must subscribe to `messages` and `message_status` events

**Solution**:
1. Go to **Meta Business Manager** → **WhatsApp** → **Configuration** → **Webhooks**
2. Verify webhook URL: `https://yourdomain.com/api/whatsapp/webhook`
3. Verify token matches your `verify_token` in database
4. Subscribe to events:
   - ✅ `messages` (incoming messages)
   - ✅ `message_status` (delivery status updates)

**Test Webhook**:
```bash
# Check if webhook is receiving events
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep "WhatsApp Webhook"
```

---

### 3. 🔴 Account Quality Rating Issues

**Issue**: Low quality rating can cause delivery delays or blocks.

**Quality Rating Factors**:
- **High**: > 4.5 stars (good delivery)
- **Medium**: 3.5-4.5 stars (may have delays)
- **Low**: < 3.5 stars (delivery issues, account restrictions)

**How to Maintain High Rating**:
- ✅ Send only to opted-in users
- ✅ Use approved templates
- ✅ Respond to user messages within 24 hours
- ✅ Avoid spam patterns
- ✅ Don't send promotional content without consent

**Check Quality Rating**:
1. Go to **Meta Business Manager** → **WhatsApp** → **Phone Numbers**
2. Click on your phone number
3. Check **"Quality rating"** section

---

### 4. 🔴 24-Hour Messaging Window

**Policy**: You can only send **free-form messages** within 24 hours of the last user message.

**Outside 24-Hour Window**:
- ✅ Must use **pre-approved templates**
- ✅ Templates must be approved by Meta
- ✅ Cannot send promotional content

**Check Window**:
- Last user message timestamp is tracked by Meta
- If > 24 hours, only templates work

---

### 5. 🔴 Opt-In Requirements

**Policy**: Users must explicitly opt-in to receive messages.

**Valid Opt-In Methods**:
- ✅ User sends you a message first
- ✅ User checks a box on your website
- ✅ User provides phone number during transaction
- ✅ User explicitly requests notifications

**Invalid**:
- ❌ Scraped phone numbers
- ❌ Purchased contact lists
- ❌ No explicit consent

**Consequences of Violation**:
- Users can block your number
- Account quality rating drops
- Account may be suspended
- Messages won't deliver

---

### 6. 🔴 Phone Number Format Issues

**Correct Format**: `919784921431` (no + sign, no spaces, no dashes)

**Incorrect Formats**:
- ❌ `+919784921431` (has + sign)
- ❌ `91 9784921431` (has space)
- ❌ `91-9784921431` (has dash)
- ❌ `09784921431` (has leading zero)

**Check**: Our code formats phone numbers correctly, but verify in logs.

---

### 7. 🔴 Account Verification Status

**Test Account**:
- Limited to allowed recipient list
- May have delivery delays
- Cannot send to all numbers

**Production Account**:
- No recipient list restriction
- Better delivery rates
- Requires business verification

**Check Status**:
1. Go to **Meta Business Manager** → **WhatsApp** → **Phone Numbers**
2. Check account status (Test/Production)

---

### 8. 🔴 Template Approval Status

**Issue**: Template must be approved before use.

**Template States**:
- ✅ **Approved** - Can be used
- ⏳ **Pending** - Waiting for approval
- ❌ **Rejected** - Cannot be used (fix and resubmit)

**Check Template Status**:
1. Go to **Meta Business Manager** → **WhatsApp** → **Message Templates**
2. Check status of each template
3. Ensure all templates are **Approved**

---

## Troubleshooting Steps

### Step 1: Check Webhook Logs

```bash
# Check if webhook is receiving delivery status updates
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep -E "(Webhook|Status Update|delivered|failed)"
```

**Expected**: You should see status updates like:
```
WhatsApp Message Status Update: status=delivered
WhatsApp Message Status Update: status=failed
```

**If No Webhook Logs**: Webhook is not configured or not receiving events.

---

### Step 2: Check Notification Logs

```bash
# Check notification log status
php artisan tinker --execute="
\$logs = \Modules\Whatsapp\Entities\WhatsAppNotificationLog::where('created_at', '>=', now()->subHours(24))
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get(['id', 'status', 'whatsapp_message_id', 'recipient_phone', 'error_message']);
foreach (\$logs as \$log) {
    echo 'ID: ' . \$log->id . ' | Status: ' . \$log->status . ' | Phone: ' . \$log->recipient_phone . PHP_EOL;
    if (\$log->error_message) {
        echo '  Error: ' . \$log->error_message . PHP_EOL;
    }
}
"
```

**Check Status Values**:
- `pending` - Not yet sent
- `sent` - Sent but not delivered
- `delivered` - Delivered to device
- `read` - Read by recipient
- `failed` - Failed to deliver

---

### Step 3: Verify Phone Numbers in Allowed List

**For Test Accounts**:
1. Go to **Meta Business Manager** → **WhatsApp** → **Phone Numbers**
2. Click your phone number
3. Go to **"Test phone numbers"** section
4. Verify all recipient numbers are listed
5. Format: `919784921431` (no + sign)

**Common Issue**: Phone numbers added but in wrong format.

---

### Step 4: Check Account Quality Rating

1. Go to **Meta Business Manager** → **WhatsApp** → **Phone Numbers**
2. Click your phone number
3. Check **"Quality rating"**
4. If low, review messaging practices

---

### Step 5: Verify Template Status

1. Go to **Meta Business Manager** → **WhatsApp** → **Message Templates**
2. Check each template status
3. Ensure all are **Approved**
4. If rejected, fix issues and resubmit

---

## WhatsApp Business API Policies Summary

### ✅ Allowed:
- Transactional messages (order confirmations, payment receipts)
- Customer service replies (within 24 hours)
- Pre-approved template messages
- Messages to opted-in users

### ❌ Prohibited:
- Spam messages
- Promotional content without consent
- Messages outside 24-hour window (without template)
- Messages to non-opted-in users
- Abusive or illegal content
- Bulk messaging without proper opt-in

### 📊 Messaging Limits (Based on Account Tier):
- **Tier 1**: 1,000 unique contacts per 24 hours
- **Tier 2**: 10,000 unique contacts per 24 hours
- **Tier 3**: 100,000 unique contacts per 24 hours
- **Tier 4**: Unlimited contacts

**To Increase Tier**: Send messages to more unique contacts while maintaining high quality rating.

---

## Regional Restrictions

### United States (As of April 2025):
- ❌ **Marketing messages** are paused for U.S. numbers
- ✅ **Transactional messages** still allowed (order confirmations, receipts, etc.)
- ✅ **Customer service replies** within 24 hours allowed

**Impact**: If sending to U.S. numbers, only transactional templates work.

---

## 🔴 YOUR SPECIFIC ISSUE: Messages "Sent" But Never "Delivered"

**Diagnosis**:
- ✅ Webhook IS working (receiving "sent" status updates)
- ❌ Webhook NOT receiving "delivered" status updates
- ❌ All messages stuck at "sent" status

**This indicates**:
1. **Opt-In Issues**: Users haven't explicitly consented (most likely)
2. **Quality Rating**: Account quality rating may be low
3. **User Blocked**: Recipients may have blocked your number
4. **Template Compliance**: Templates may not comply with WhatsApp policies
5. **Device Offline**: Recipients' devices offline (but would eventually deliver)

**Immediate Actions**:
1. **Check Account Quality Rating** in Meta Business Manager
2. **Verify Opt-In Records** - Ensure users explicitly consented
3. **Test with a number that sent you a message first** (24-hour window)
4. **Check if recipients blocked your number**
5. **Review template compliance** - Ensure templates follow WhatsApp guidelines

---

## Quick Fix Checklist

When messages are "accepted" but not delivered:

- [ ] **Check if test account** → Add numbers to allowed recipient list
- [ ] **Verify webhook configured** → Check Meta Business Manager webhook settings
- [ ] **Check webhook logs** → Verify delivery status updates are received
- [ ] **Verify phone number format** → Must be `919784921431` (no + sign)
- [ ] **Check account quality rating** → Should be High (> 4.5)
- [ ] **Verify template status** → All templates must be Approved
- [ ] **Check opt-in status** → Users must have consented
- [ ] **Verify 24-hour window** → Use templates if outside window
- [ ] **Check for account restrictions** → Review Meta Business Manager for warnings

---

## Testing Delivery Status

### Test Webhook Reception:
```bash
# Monitor webhook logs in real-time
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep -E "(Webhook|Status Update)"
```

### Test Message Send:
1. Send a test message
2. Check logs for "accepted" status
3. Wait 5-10 seconds
4. Check webhook logs for "delivered" or "failed" status
5. If no webhook logs → Webhook not configured properly

---

## Common Error Codes

| Error Code | Meaning | Solution |
|------------|---------|----------|
| `131030` | Recipient not in allowed list | Add to test phone numbers list |
| `131026` | Template not found | Verify template name and approval status |
| `131047` | Rate limit exceeded | Wait and retry, or upgrade account tier |
| `131051` | Message outside 24-hour window | Use approved template |
| `131048` | Account quality rating too low | Improve messaging practices |

---

## Best Practices for Reliable Delivery

1. ✅ **Always use templates** for transactional messages
2. ✅ **Obtain explicit opt-in** from users
3. ✅ **Respond within 24 hours** to maintain messaging window
4. ✅ **Monitor quality rating** regularly
5. ✅ **Use webhooks** to track delivery status
6. ✅ **Test with allowed numbers** before production
7. ✅ **Avoid spam patterns** (same message to many users)
8. ✅ **Personalize messages** when possible
9. ✅ **Respect user preferences** (opt-out requests)
10. ✅ **Keep templates simple** and clear

---

## Need Help?

If messages are still not delivering after checking all above:

1. **Check Meta Business Manager** for account warnings
2. **Review webhook logs** for delivery status updates
3. **Contact Meta Support** if account restrictions are unclear
4. **Verify all phone numbers** are in correct format
5. **Test with different numbers** to isolate the issue

---

**Last Updated**: 2025-12-10

