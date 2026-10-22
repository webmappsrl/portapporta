---
paths:
  - "app/Nova/**"
---

# Trappole: Nova

- Per il fallback company di un company_admin in `Address` usa `admin_company_id`, non `app_company_id` (che per un company_admin è null): la divergenza col campo `zone_id` è intenzionale (oc:8101)
- In `Address`, `->options()` e `->rules()` devono derivare dallo stesso Builder (`userTypeOptionsForCompany`): separarli fa divergere opzioni mostrate e valori accettati (oc:8101)
