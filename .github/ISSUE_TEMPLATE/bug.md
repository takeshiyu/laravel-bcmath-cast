---
name: Bug Fix
about: 修復既有功能的錯誤（包含 Sentry error、NPE）
title: "fix: "
labels: bug
---

## 背景


## 問題

```
（貼上 Sentry stack trace 或錯誤訊息）
```

## 架構說明
理解 `docs/*.md`

## 實作前請先

### 第一步：Root Cause Analysis（必做）
在列出任何修改檔案之前，請先探索相關檔案，找出發生錯誤的根本原因。

1. 錯誤發生在哪個檔案？
2. 什麼情況下會觸發？（是否有任何 Edge Case？）
3. 修復的最小改動點在哪裡？
4. 對於不清楚、無法確定該執行什麼任務或決策時，立即暫停並提問，勿自行想像。

### 第二步：列出修改檔案清單（必做）
Root Cause Analysis 完成後，才列出你打算新增或修改的所有檔案清單，並更新到這個 Issue 下（comment）。等確認後再動手。

## 完成條件

### 修復
- [ ] （描述修復後的預期行為）

### 通用條件
- [ ] 補充 characterization test，記錄修復前的行為，確認修復後不再發生
- [ ] `composer lint` 無警告
- [ ] `composer test` 全部通過
