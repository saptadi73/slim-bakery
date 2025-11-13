# TODO: Create Order Report by Order ID

## Tasks
- [ ] Add new method `getOrderReportById` in `app/Services/ReportService.php` to generate report for a specific order_id
- [ ] Update `routes/reports.php` to add new route `/reports/orders/{order_id}` that calls the new method
- [ ] Test the new endpoint to ensure it returns the correct report structure

## Report Structure
- Order details: id, no_order, outlet, pic, tanggal, status, keterangan
- For each product item:
  - Ordered: quantity, pic, updated_at, keterangan (from order)
  - Provided: total quantity from providers, list of providers with pic, updated_at, keterangan if any
  - Delivered: total quantity from delivery orders, list of deliveries with pic, updated_at, keterangan if any
  - Received: total quantity from receives, list of receives with pic, updated_at, keterangan
