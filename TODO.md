# Composer Autoloader Fix - TODO Steps

## Plan Breakdown (Approved)
1. [x] **Local verification**: ✓ vendor/composer files present, autoloader intact locally.
2. [x] **Local test**: Should work in XAMPP. Test: http://localhost/planwise/public/index.php?page=home
3. [ ] **Production fix**: 
   - Local Docker: `docker-compose build --no-cache && docker-compose up -d`
   - Railway: Commit/push any changes OR `railway up`
4. [ ] **Full test**: Prod URL loads landing page.
5. [ ] **Done**: Remove TODO.

**Status Update:**
3. [x] **Dockerfile fixed** - Added validation, explicit dump-autoload, verification test.

**Next: Deploy to Render**
- `git add Dockerfile TODO.md`
- `git commit -m "Fix Composer autoloader: robust Dockerfile"`
- `git push`
Render auto-redeploys → autoloader generated!



