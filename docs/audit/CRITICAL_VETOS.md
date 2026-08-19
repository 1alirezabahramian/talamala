# Critical Vetos — Final Audit Agent

If **any** veto is active, final verdict cannot be `ACCEPTED_FOR_PILOT`.

| ID | Veto | Detection |
|----|------|-----------|
| CV-01 | Critical security / tenant isolation failure | CAP-001/021 invalid state or current-run HTTP/CORS security gate failure |
| CV-02 | Kimia/financial unsafe or ungrounded | Write enabled in `.env.example`, or settlement/price capability claimed implemented while GT remains |
| CV-03 | Exact release SHA CI missing / mismatched | No explicit success attestation matching current `git HEAD` |
| CV-04 | Material contract drift / unverified parity | `openapi_parity` does not PASS in the current audit run |
| CV-05 | Payment financial in required scope unproven | CAP-019 claimed IMPLEMENTED without archived proof |
| CV-06 | Production deployment gate incomplete | DEPLOY / release-build / pilot-preflight missing |
| CV-07 | Required physical/live evidence missing | Reserved for in-scope live claim evidence |
| CV-08 | Any **in-scope critical** checklist item 🔴 RED | Agent item is critical + pilot_scope=in + RED |
| CV-09 | Stale/fake version evidence | repo `VERSION` differs from registry pin |

## Phase-1 pilot special rule

Items marked `pilot_scope: out` that are BLOCKED for GT do **not** by themselves veto the bounded pilot when RELEASE_SCOPE excludes them. They remain ⚫ and keep full product closure blocked.

## Evidence rule

Presence of a test script or CI workflow is **not** evidence that it passed. Current-run gate results and exact-SHA attestation are required.
