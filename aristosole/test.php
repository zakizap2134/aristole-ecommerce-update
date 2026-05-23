<!-- Technical Note -->
        <div class="card">
            <div class="card-header" style="background: #6c5ce7;">
                📌 Database Features Implemented
            </div>
            <div class="card-body">
                <ul>
                    <li><strong>SQL VIEW:</strong> vw_sales_report - joins products, brands, categories, order_items (4 tables)</li>
                    <li><strong>SQL TRIGGER:</strong> trg_reduce_stock - automatically updates stock when order placed</li>
                    <li><strong>SQL STORED PROCEDURE:</strong> sp_checkout - handles complete checkout with transaction</li>
                    <li><strong>TRANSACTION:</strong> BEGIN/COMMIT/ROLLBACK in checkout process</li>
                    <li><strong>PREPARED STATEMENTS:</strong> 100% of SQL queries use prepared statements</li>
                    <li><strong>INDEX:</strong> idx_sku on products.sku for faster search</li>
                    <li><strong>PASSWORD HASHING:</strong> password_hash() and password_verify()</li>
                </ul>
            </div>
        </div>