# Fix Failing Tests

## Auth Tests
- [ ] Add output buffering in tests/bootstrap.php to prevent headers sent error
- [ ] Ensure all session_start calls are wrapped in checks (already done)

## QR Code Tests
- [ ] Add validation in QRCode::generate to reject lesson_id <= 0
- [ ] Seed test lesson with ID=999 in database
- [ ] Ensure QR directory exists and is writable
- [ ] Check GD extension is enabled

## Run Tests
- [ ] Run PHPUnit to verify fixes
