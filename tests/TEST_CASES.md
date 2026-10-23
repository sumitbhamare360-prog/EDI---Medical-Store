# Manual test cases (TC-01 to TC-12)

1. **TC-01 Login:** enter each seeded role login; expect role-appropriate dashboard.
2. **TC-02 Access control:** cashier opens admin URL; expect HTTP 403 and audit record.
3. **TC-03 Medicine search:** search name/generic; expect matching active medicines.
4. **TC-04 Queue:** add customer, call next, complete/cancel; expect token state updates.
5. **TC-05 FEFO:** sell enough to span two valid batches; earliest expiry is used first.
6. **TC-06 Expiry:** expired stock is excluded from POS and visible in expiry report.
7. **TC-07 Prescription:** Rx medicine without valid ref is rejected; valid ref is saved.
8. **TC-08 Purchase:** create and receive PO; expect batch stock increase.
9. **TC-09 Return limit:** return item; reject return beyond unreturned quantity.
10. **TC-10 Return restock:** restock only original unexpired allocations for that item.
11. **TC-11 Reports:** filter a date range and print each report.
12. **TC-12 Rollback:** inject a mid-sale exception; invoice, allocations, ledger, and stock roll back.
