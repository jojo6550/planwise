# PlanWise Deployment Fix - TODO

## Current Status
- [x] Analyze deployment errors (Apache 403/500 errors, internal redirects)
- [x] Identify root cause (DocumentRoot mismatch, .htaccess location, hardcoded URLs)

## Implementation Plan
- [ ] Update Dockerfile to configure Apache DocumentRoot to /var/www/html/public
- [ ] Move .htaccess from root to public/ directory
- [ ] Update hardcoded URLs in public/index.php for production deployment
- [ ] Test deployment configuration

## Verification
- [ ] Verify Apache serves from public/ directory
- [ ] Confirm routing works without redirect loops
- [ ] Ensure security rules (.htaccess) are applied correctly
