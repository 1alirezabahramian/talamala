# Critical Vetos — Final Audit Agent

If **any** CV-* veto is active, the Pilot verdict cannot be `ACCEPTED_FOR_PILOT`.

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

Items marked `pilot_scope: out` that are BLOCKED for GT do **not** by themselves veto the bounded pilot. They remain ⚫ and still block Full Release when classified `release_required`.

## Release Vetos — Release mode only

Every active CV-* also blocks Release. In addition:

| ID | Veto | Detection |
|----|------|-----------|
| RV-01 | Release-required item not GREEN | Any `release_required` item is MISSING/BLOCKED/RED/ORANGE/YELLOW |
| RV-02 | Release-required critical not GREEN | Required critical item is not GREEN |
| RV-03 | Ungrounded financial claim in Release | CV-02 active / ungrounded financial implementation claim |
| RV-04 | Release scope registry missing/invalid | Registry unreadable/empty, duplicate or unknown IDs, required/deferred overlap, or any checklist ID unclassified |

`release_deferred` items do not fire RV-01/RV-02.

**Threshold is never lowered:** Release first requires the Pilot authority to remain accepted, then adds the complete release-required gate and RV-*.

## Evidence rule

Presence of a test script or workflow is not evidence that it passed. Current-run gates and exact-SHA attestation are required.
