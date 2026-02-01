# Fix Failing Tests

## Auth Tests
- [ ] Add output buffering in tests/bootstrap.php to prevent headers sent error
- [ ] Ensure all session_start calls are wrapped in checks (already done)

## QR Code Tests
- [x] Add validation in QRCode::generate to reject lesson_id <= 0
- [x] Seed test lesson with ID=999 in database
- [x] Ensure QR directory exists and is writable
- [x] Check GD extension is enabled
- [x] Fix QR code generation to use correct library method
- [x] Update test to use lesson_id=999 and expect false for zero ID

## Run Tests
- [ ] Run PHPUnit to verify fixes
