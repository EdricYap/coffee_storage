


            <footer class="footer">
                <span>Coffee Storage Inventory System</span>
                <span>Final Project</span>
            </footer>
        </section>
    </main>
</div>
<script>
document.querySelectorAll('[data-toggle]').forEach((button) => {
    button.addEventListener('click', () => document.getElementById(button.dataset.toggle)?.classList.toggle('is-open'));
});
const transactionType = document.getElementById('transaction-type');
if (transactionType) {
    const receiptFields = document.querySelector('.receipt-fields');
    const location = document.querySelector('[name="location_id"]');
    const receivedDate = document.querySelector('[name="received_date"]');
    const expiry = document.querySelector('[name="expiry_date"]');
    const selectedItem = document.querySelector('[name="item_id"]');
    const refreshTransactionFields = () => {
        const isReceipt = transactionType.value === 'RECEIPT';
        receiptFields.hidden = !isReceipt;
        location.required = isReceipt;
        receivedDate.required = isReceipt;
        const option = selectedItem.options[selectedItem.selectedIndex];
        expiry.required = isReceipt && option?.dataset.perishable === '1';
    };
    transactionType.addEventListener('change', refreshTransactionFields);
    selectedItem.addEventListener('change', refreshTransactionFields);
    refreshTransactionFields();
}
document.querySelectorAll('.flash-close').forEach((button) => {
    button.addEventListener('click', () => button.parentElement.remove());
});
</script>
</body>
</html>
