CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "permissions"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "guard_name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "permissions_name_guard_name_unique" on "permissions"(
  "name",
  "guard_name"
);
CREATE TABLE IF NOT EXISTS "roles"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "guard_name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "roles_name_guard_name_unique" on "roles"(
  "name",
  "guard_name"
);
CREATE TABLE IF NOT EXISTS "model_has_permissions"(
  "permission_id" integer not null,
  "model_type" varchar not null,
  "model_id" integer not null,
  foreign key("permission_id") references "permissions"("id") on delete cascade,
  primary key("permission_id", "model_id", "model_type")
);
CREATE INDEX "model_has_permissions_model_id_model_type_index" on "model_has_permissions"(
  "model_id",
  "model_type"
);
CREATE TABLE IF NOT EXISTS "model_has_roles"(
  "role_id" integer not null,
  "model_type" varchar not null,
  "model_id" integer not null,
  foreign key("role_id") references "roles"("id") on delete cascade,
  primary key("role_id", "model_id", "model_type")
);
CREATE INDEX "model_has_roles_model_id_model_type_index" on "model_has_roles"(
  "model_id",
  "model_type"
);
CREATE TABLE IF NOT EXISTS "role_has_permissions"(
  "permission_id" integer not null,
  "role_id" integer not null,
  foreign key("permission_id") references "permissions"("id") on delete cascade,
  foreign key("role_id") references "roles"("id") on delete cascade,
  primary key("permission_id", "role_id")
);
CREATE TABLE IF NOT EXISTS "companies"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "slug" varchar not null,
  "domain" varchar,
  "description" text,
  "logo_url" varchar,
  "timezone" varchar not null default 'UTC',
  "currency" varchar not null default 'USD',
  "settings" text,
  "status" varchar check("status" in('active', 'inactive', 'suspended')) not null default 'active',
  "plan" varchar check("plan" in('starter', 'professional', 'enterprise')) not null default 'starter',
  "plan_expires_at" datetime,
  "is_trial" tinyint(1) not null default '1',
  "trial_ends_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "api_integrations_enabled" tinyint(1) not null default '0',
  "webhooks_enabled" tinyint(1) not null default '0',
  "real_time_sync_enabled" tinyint(1) not null default '0',
  "api_calls_this_month" integer not null default '0',
  "max_api_calls_per_month" integer not null default '0',
  "stripe_customer_id" varchar,
  "stripe_subscription_id" varchar,
  "last_payment_at" datetime,
  "next_billing_date" datetime
);
CREATE INDEX "companies_status_plan_index" on "companies"("status", "plan");
CREATE INDEX "companies_is_trial_trial_ends_at_index" on "companies"(
  "is_trial",
  "trial_ends_at"
);
CREATE UNIQUE INDEX "companies_slug_unique" on "companies"("slug");
CREATE TABLE IF NOT EXISTS "stores"(
  "id" integer primary key autoincrement not null,
  "company_id" integer not null,
  "name" varchar not null,
  "shopify_domain" varchar not null,
  "shopify_store_id" varchar,
  "shopify_access_token" varchar,
  "currency" varchar not null default 'USD',
  "country_code" varchar,
  "timezone" varchar,
  "description" text,
  "logo_url" varchar,
  "shopify_webhook_endpoints" text,
  "status" varchar check("status" in('active', 'inactive', 'connecting', 'error')) not null default 'connecting',
  "last_sync_at" datetime,
  "sync_errors" text,
  "settings" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "stripe_secret_key" text,
  "stripe_publishable_key" varchar,
  "stripe_sync_enabled" tinyint(1) not null default '0',
  "last_stripe_sync" datetime,
  foreign key("company_id") references "companies"("id") on delete cascade
);
CREATE INDEX "stores_company_id_status_index" on "stores"(
  "company_id",
  "status"
);
CREATE INDEX "stores_shopify_domain_index" on "stores"("shopify_domain");
CREATE INDEX "stores_last_sync_at_index" on "stores"("last_sync_at");
CREATE UNIQUE INDEX "stores_shopify_domain_unique" on "stores"(
  "shopify_domain"
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "company_id" integer,
  "user_type" varchar check("user_type" in('admin', 'company_owner', 'partner', 'viewer')) not null default 'partner',
  "preferences" text,
  "last_login_at" datetime,
  "avatar_url" varchar,
  "is_active" tinyint(1) not null default '1',
  foreign key("company_id") references "companies"("id") on delete cascade
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE INDEX "users_company_id_user_type_index" on "users"(
  "company_id",
  "user_type"
);
CREATE INDEX "users_company_id_is_active_index" on "users"(
  "company_id",
  "is_active"
);
CREATE INDEX "users_last_login_at_index" on "users"("last_login_at");
CREATE TABLE IF NOT EXISTS "import_batches"(
  "id" integer primary key autoincrement not null,
  "batch_id" varchar not null,
  "company_id" integer not null,
  "initiated_by" integer not null,
  "import_type" varchar check("import_type" in('csv', 'shopify', 'stripe', 'paypal', 'manual', 'api')) not null default 'csv',
  "source_type" varchar check("source_type" in('payoneer', 'mercury', 'stripe', 'shopify_payments', 'bank', 'other')),
  "original_filename" varchar,
  "file_path" varchar,
  "file_size" integer,
  "file_hash" varchar,
  "mime_type" varchar,
  "status" varchar check("status" in('pending', 'processing', 'completed', 'failed', 'cancelled')) not null default 'pending',
  "total_records" integer not null default '0',
  "processed_records" integer not null default '0',
  "successful_records" integer not null default '0',
  "failed_records" integer not null default '0',
  "duplicate_records" integer not null default '0',
  "skipped_records" integer not null default '0',
  "started_at" datetime,
  "completed_at" datetime,
  "processing_time_seconds" integer,
  "import_settings" text,
  "metadata" text,
  "results_summary" text,
  "errors" text,
  "error_message" text,
  "total_amount" numeric,
  "currency" varchar,
  "requires_review" tinyint(1) not null default '0',
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("company_id") references "companies"("id") on delete cascade,
  foreign key("initiated_by") references "users"("id") on delete restrict
);
CREATE INDEX "import_batches_company_id_status_index" on "import_batches"(
  "company_id",
  "status"
);
CREATE INDEX "import_batches_import_type_source_type_index" on "import_batches"(
  "import_type",
  "source_type"
);
CREATE INDEX "import_batches_initiated_by_created_at_index" on "import_batches"(
  "initiated_by",
  "created_at"
);
CREATE INDEX "import_batches_status_created_at_index" on "import_batches"(
  "status",
  "created_at"
);
CREATE INDEX "import_batches_file_hash_index" on "import_batches"("file_hash");
CREATE UNIQUE INDEX "import_batches_batch_id_unique" on "import_batches"(
  "batch_id"
);
CREATE TABLE IF NOT EXISTS "payment_processor_accounts"(
  "id" integer primary key autoincrement not null,
  "company_id" integer not null,
  "processor_type" varchar not null,
  "account_identifier" varchar,
  "currency" varchar not null default 'USD',
  "current_balance" numeric not null default '0',
  "pending_balance" numeric not null default '0',
  "pending_payouts" numeric not null default '0',
  "metadata" text,
  "last_sync_at" datetime,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("company_id") references "companies"("id") on delete cascade
);
CREATE INDEX "payment_processor_accounts_company_id_processor_type_index" on "payment_processor_accounts"(
  "company_id",
  "processor_type"
);
CREATE INDEX "payment_processor_accounts_processor_type_currency_index" on "payment_processor_accounts"(
  "processor_type",
  "currency"
);
CREATE INDEX "payment_processor_accounts_is_active_processor_type_index" on "payment_processor_accounts"(
  "is_active",
  "processor_type"
);
CREATE TABLE IF NOT EXISTS "partnerships"(
  "id" integer primary key autoincrement not null,
  "store_id" integer not null,
  "user_id" integer,
  "ownership_percentage" numeric not null,
  "role" varchar not null default('partner'),
  "role_description" text,
  "partnership_start_date" date not null,
  "partnership_end_date" date,
  "status" varchar not null default('ACTIVE'),
  "permissions" text,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  "partner_email" varchar,
  "invitation_token" varchar,
  "invited_at" datetime,
  "activated_at" datetime,
  foreign key("store_id") references stores("id") on delete cascade on update no action,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "partnerships_invitation_token_index" on "partnerships"(
  "invitation_token"
);
CREATE UNIQUE INDEX "partnerships_invitation_token_unique" on "partnerships"(
  "invitation_token"
);
CREATE INDEX "partnerships_ownership_percentage_index" on "partnerships"(
  "ownership_percentage"
);
CREATE INDEX "partnerships_store_id_status_index" on "partnerships"(
  "store_id",
  "status"
);
CREATE UNIQUE INDEX "partnerships_store_id_user_id_unique" on "partnerships"(
  "store_id",
  "user_id"
);
CREATE INDEX "partnerships_user_id_status_index" on "partnerships"(
  "user_id",
  "status"
);
CREATE TABLE IF NOT EXISTS "bank_accounts"(
  "id" integer primary key autoincrement not null,
  "company_id" integer not null,
  "bank_type" varchar not null,
  "account_name" varchar,
  "account_number" text,
  "routing_number" text,
  "iban" varchar,
  "swift_code" varchar,
  "currency" varchar not null default('USD'),
  "current_balance" numeric not null default('0'),
  "is_primary" tinyint(1) not null default('0'),
  "is_active" tinyint(1) not null default('1'),
  "metadata" text,
  "last_sync_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "bank_name" varchar,
  "bank_branch" varchar,
  "country_code" varchar not null default 'US',
  "bank_address" varchar,
  "bank_phone" varchar,
  "bank_website" varchar,
  "bic_code" varchar,
  "sort_code" varchar,
  "bsb_number" varchar,
  "institution_number" varchar,
  "bank_code" varchar,
  "custom_fields" text,
  foreign key("company_id") references companies("id") on delete cascade on update no action
);
CREATE INDEX "bank_accounts_bank_type_currency_index" on "bank_accounts"(
  "bank_type",
  "currency"
);
CREATE INDEX "bank_accounts_company_id_is_primary_index" on "bank_accounts"(
  "company_id",
  "is_primary"
);
CREATE UNIQUE INDEX "bank_accounts_company_id_is_primary_unique" on "bank_accounts"(
  "company_id",
  "is_primary"
);
CREATE INDEX "bank_accounts_is_active_is_primary_index" on "bank_accounts"(
  "is_active",
  "is_primary"
);
CREATE TABLE IF NOT EXISTS "transactions"(
  "id" integer primary key autoincrement not null,
  "store_id" integer not null,
  "created_by" integer not null,
  "transaction_id" varchar not null,
  "external_id" varchar,
  "reference_number" varchar,
  "amount" numeric not null,
  "currency" varchar not null default('USD'),
  "exchange_rate" numeric not null default('1'),
  "amount_usd" numeric,
  "category" varchar not null,
  "subcategory" varchar,
  "type" varchar not null,
  "status" varchar not null default('pending'),
  "description" varchar not null,
  "notes" text,
  "metadata" text,
  "transaction_date" datetime not null,
  "processed_at" datetime,
  "source" varchar not null default('manual'),
  "source_details" varchar,
  "is_reconciled" tinyint(1) not null default('0'),
  "reconciled_at" datetime,
  "reconciled_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "import_batch_id" integer,
  "payment_processor_type" varchar,
  "payment_processor_id" integer,
  "is_pending_payout" tinyint(1) not null default('0'),
  "payout_date" datetime,
  "is_personal_expense" tinyint(1) not null default('0'),
  "partner_id" integer,
  "is_adjustment" tinyint(1) not null default('0'),
  "adjustment_type" varchar,
  foreign key("import_batch_id") references import_batches("id") on delete set null on update no action,
  foreign key("store_id") references stores("id") on delete cascade on update no action,
  foreign key("created_by") references users("id") on delete restrict on update no action,
  foreign key("reconciled_by") references users("id") on delete no action on update no action,
  foreign key("payment_processor_id") references payment_processor_accounts("id") on delete no action on update no action,
  foreign key("partner_id") references users("id") on delete no action on update no action
);
CREATE INDEX "transactions_category_status_transaction_date_index" on "transactions"(
  "category",
  "status",
  "transaction_date"
);
CREATE INDEX "transactions_currency_amount_index" on "transactions"(
  "currency",
  "amount"
);
CREATE INDEX "transactions_import_batch_id_created_at_index" on "transactions"(
  "import_batch_id",
  "created_at"
);
CREATE INDEX "transactions_is_adjustment_adjustment_type_index" on "transactions"(
  "is_adjustment",
  "adjustment_type"
);
CREATE INDEX "transactions_is_personal_expense_partner_id_index" on "transactions"(
  "is_personal_expense",
  "partner_id"
);
CREATE INDEX "transactions_is_reconciled_status_index" on "transactions"(
  "is_reconciled",
  "status"
);
CREATE INDEX "transactions_payment_processor_type_is_pending_payout_index" on "transactions"(
  "payment_processor_type",
  "is_pending_payout"
);
CREATE INDEX "transactions_source_external_id_index" on "transactions"(
  "source",
  "external_id"
);
CREATE INDEX "transactions_store_id_category_type_index" on "transactions"(
  "store_id",
  "category",
  "type"
);
CREATE INDEX "transactions_store_id_transaction_date_index" on "transactions"(
  "store_id",
  "transaction_date"
);
CREATE INDEX "transactions_transaction_date_status_index" on "transactions"(
  "transaction_date",
  "status"
);
CREATE UNIQUE INDEX "transactions_transaction_id_unique" on "transactions"(
  "transaction_id"
);

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2025_06_28_103241_create_permission_tables',1);
INSERT INTO migrations VALUES(5,'2025_06_28_104424_create_companies_table',1);
INSERT INTO migrations VALUES(6,'2025_06_28_104433_create_stores_table',1);
INSERT INTO migrations VALUES(7,'2025_06_28_104435_create_partnerships_table',1);
INSERT INTO migrations VALUES(8,'2025_06_28_104437_create_transactions_table',1);
INSERT INTO migrations VALUES(9,'2025_06_28_104439_add_company_id_to_users_table',1);
INSERT INTO migrations VALUES(10,'2025_06_28_105045_add_soft_deletes_to_companies_table',1);
INSERT INTO migrations VALUES(11,'2025_06_28_110541_create_import_batches_table',1);
INSERT INTO migrations VALUES(12,'2025_06_28_110705_add_import_batch_id_to_transactions_table',1);
INSERT INTO migrations VALUES(13,'2025_06_28_114943_add_soft_deletes_to_stores_table',1);
INSERT INTO migrations VALUES(14,'2025_06_28_120000_create_payment_processor_accounts_table',1);
INSERT INTO migrations VALUES(15,'2025_06_28_121000_add_payment_processor_fields_to_transactions_table',1);
INSERT INTO migrations VALUES(16,'2025_06_28_122000_create_bank_accounts_table',1);
INSERT INTO migrations VALUES(17,'2025_06_28_140413_add_invitation_fields_to_partnerships_table',1);
INSERT INTO migrations VALUES(18,'2025_06_28_143133_make_user_id_nullable_in_partnerships_table',1);
INSERT INTO migrations VALUES(19,'2025_06_28_152817_add_flexible_bank_fields_to_bank_accounts_table',1);
INSERT INTO migrations VALUES(20,'2025_06_28_160048_add_plan_fields_to_companies_table',1);
INSERT INTO migrations VALUES(21,'2025_06_28_164516_add_stripe_fields_to_stores_table',1);
