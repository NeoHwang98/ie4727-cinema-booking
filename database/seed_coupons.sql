USE cinema_portal;

INSERT INTO coupons (code, description, discount_type, value, min_total, expires_at, active)
VALUES
  ('WELCOME10', '10% off for new users', 'percent', 10.00, 0.00, '2026-01-09', 1)
ON DUPLICATE KEY UPDATE description=VALUES(description), discount_type=VALUES(discount_type), value=VALUES(value), min_total=VALUES(min_total), expires_at=VALUES(expires_at), active=VALUES(active);

INSERT INTO coupons (code, description, discount_type, value, min_total, expires_at, active)
VALUES
  ('BUY2SAVE5', '$5 off orders over $36', 'amount', 5.00, 36.00, '2026-02-08', 1)
ON DUPLICATE KEY UPDATE description=VALUES(description), discount_type=VALUES(discount_type), value=VALUES(value), min_total=VALUES(min_total), expires_at=VALUES(expires_at), active=VALUES(active);

INSERT INTO coupons (code, description, discount_type, value, min_total, expires_at, active)
VALUES
  ('MOVIEBUFF15', '15% off orders over $60', 'percent', 15.00, 60.00, '2026-03-31', 1)
ON DUPLICATE KEY UPDATE description=VALUES(description), discount_type=VALUES(discount_type), value=VALUES(value), min_total=VALUES(min_total), expires_at=VALUES(expires_at), active=VALUES(active);

