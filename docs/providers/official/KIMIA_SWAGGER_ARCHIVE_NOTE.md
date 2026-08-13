# Kimia Swagger Archive Note

**Source:** historical GoldPlatform repository `swagger.json`  
**SHA-256 (git blob):** `ea3de1aa56c6f2a940eba24a6c4f57eb9fc904ed`  
**OpenAPI:** 3.0.4 — title Kimia API — version v1  
**Server evidence:** `http://94.101.184.26:11000`  
**Auth:** Basic  

## Confirmed for Talamala Read path
- GET `/api/account` query param **`Type`** (not accountType)
- GET `/api/account/groups` query param **`accountType`**
- GET `/api/voucher/balance/{id}`, balances, transactions (pageNumber from 0)
- GET `/api/product`, `/api/product/coins`, `/api/product/currencies`
- BalanceDto: Weight, Money, CurrencyId, CurrencySymbol (Money meaning depends on CurrencyId)
- RequestId (UUID) on mutation schemas for idempotency

## Write schemas present in Swagger (implementation still gated)
- AdjustmentRequest, ExchangeRequest, ExchangeCurrencyRequest
- TradeCash, TradeCurrency, TradeBarcode, TransferRequest
- Action codes on exchange: **32 = خرید, 64 = فروش** (Swagger)
- Action on cash/transfer: **2 = دریافت, 4 = پرداخت**

Talamala must still perform controlled readback after any write and must not invent bodies beyond this schema.
Historical operational codes 3/4 remain PARTIAL until end-to-end verified against live/runtime.

**Status:** Archived as provider evidence for Stage 3 Read; Write remains blocked pending owner-controlled live verification.
