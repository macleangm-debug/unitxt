# unitxt Auth Testing Playbook

## MongoDB verification
```
mongosh
use unitxt_db
db.users.find({role: "admin"}).pretty()
db.users.findOne({role: "admin"}, {password_hash: 1})
```
Expect bcrypt hash starting with `$2b$`.

## Indexes
```
db.users.getIndexes()
db.login_attempts.getIndexes()
db.password_reset_tokens.getIndexes()
```

## API smoke (use external REACT_APP_BACKEND_URL)
```
API=https://unitxt-global.preview.emergentagent.com
curl -c /tmp/c.txt -X POST $API/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@unitxt.io","password":"Admin@2026"}'
curl -b /tmp/c.txt $API/api/auth/me
```
Login returns `{ user: {...} }` and sets httpOnly `access_token` + `refresh_token`.

## Roles to test
- admin@unitxt.io / Admin@2026  (super_admin)
- reseller@unitxt.io / Reseller@2026  (reseller)
- client@unitxt.io / Client@2026  (client)

## Endpoints
- POST /api/auth/register
- POST /api/auth/login
- POST /api/auth/logout
- GET  /api/auth/me
- POST /api/auth/refresh
- POST /api/auth/forgot-password
- POST /api/auth/reset-password
