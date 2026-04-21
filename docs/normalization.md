# Database Normalization in Planwise

## Introduction
Database normalization is a design technique to organize data in a relational database to minimize redundancy and dependency issues. It prevents insertion/update/deletion anomalies by structuring tables into normal forms (NF). Planwise's MySQL schema (`database/planwise_db(2).sql`) follows **3rd Normal Form (3NF)**, balancing integrity and performance for lesson plan management.

**Key Benefits in Planwise**:
- Single source of truth (e.g., update `roles.role_name` once).
- Efficient queries via INDEXes/FKs.
- Data integrity via CASCADE actions.

## Normal Forms Explained with Planwise Examples

### 1NF: First Normal Form (Atomicity)
**Rule**: All fields contain atomic (single) values; no repeating groups/lists.

**Violation Example**: `lesson_plans.subjects` as 'Math,Science' (split needed).
**Planwise Compliance**:
- `lesson_plans.subject` VARCHAR(100) - single value.
- `lesson_sections` separate table for multiple sections per plan.

| Table | Atomic Example |
|-------|----------------|
| users | `email` single string |
| lesson_plans | `title`, `objectives` TEXT (no arrays) |

### 2NF: Second Normal Form (Full Functional Dependency)
**Rule**: 1NF + non-prime attributes fully depend on entire PK (no partial deps). Requires composite PK.

**Planwise**: No composite PKs, all single INT AUTO_INCREMENT → inherently 2NF. E.g., `files.file_path` depends fully on `file_id`.

### 3NF: Third Normal Form (No Transitive Dependencies)
**Rule**: 2NF + non-prime attributes depend only on PK, not other non-prime (no A→B→C).

**Violation Example**: `users.role_name` directly (transitive: user_id → role_id → role_name).
**Planwise Fix**: Separate `roles` table with FK.

**Key Relationships**:
```
users (user_id PK) → role_id FK → roles (role_id PK)
lesson_plans (lesson_id PK) ← user_id FK, → lesson_sections/files/qr_codes (FKs)
activity_logs/password_resets/remember_tokens → user_id FK
```
- UNIQUE `users.email`, `roles.role_name`, `qr_codes.lesson_id` prevent duplicates.
- INDEXes: `idx_lesson_user` etc. for joins.

### Higher Forms (BCNF/4NF)
Planwise doesn't need (no overlapping candidates, no multi-valued deps). 3NF sufficient.

## Schema Normalization Analysis
```
Core Entities:
- roles (lookup)
  ↓
users ───────→ lesson_plans ───────→ lesson_sections
  ↑              ↓                ↑
activity_logs    files/qr_codes
  ↓
password_resets/remember_tokens
```
**Why 3NF**:
1. Atomic columns ✓
2. Full deps on PK ✓
3. No transitive (FKs to lookup tables) ✓
4. Constraints enforce integrity.

**Anomaly Prevention**:
- Delete user → CASCADE deletes plans/logs (no orphans).
- Update role → propagates via FK UPDATE CASCADE.

## Denormalization Trade-offs
Future: Add `user_role_name` denormalized for read perf, but risks inconsistency (avoided here).

## Verification
Run `DESCRIBE table_name;` or Workbench to confirm. Schema generated via seeds/migrations.

See `docs/technical-documentation.md` for full ERD/project context.

