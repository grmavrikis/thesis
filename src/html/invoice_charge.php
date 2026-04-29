    <div class="charge-popup-container" id="chargePopup">
        <div class="charge-popup-content">
            <span class="close-popup closeChargePopup" id="closeChargePopup">&times;</span>
            <h3 class="filter-title"><?php echo TEXT_ADD_INVOICE_CHARGE; ?></h3>
            <hr class="filter-divider">

            <div class="charge-form-body">
                <div class="form-input">
                    <label for="charge_description"><?php echo TEXT_INVOICE_CHARGE_DESCRIPTION; ?></label>
                    <input type="text" id="charge_description" placeholder="<?php echo TEXT_INVOICE_CHARGE_DESCRIPTION_PLACEHOLDER; ?>">
                </div>

                <div class="form-row">
                    <div class="form-input flex-1">
                        <label for="charge_clean_amount"><?php echo TEXT_INVOICE_CHARGE_VAT; ?></label>
                        <input type="number" id="charge_clean_amount" step="0.01" class="calc-trigger">
                    </div>
                    <div class="form-input flex-1">
                        <label for="charge_tax"><?php echo TEXT_INVOICE_CHARGE_VAT; ?></label>


                        <select id="charge_tax" class="calc-trigger">
                            <?php
                            $sql = "SELECT t.tax_id, 
                                    ROUND(t.factor, 2) AS factor,
                                    CONCAT(FORMAT(t.factor * 100, 2, 'el_GR'), '%') AS factor_percent
                                    FROM tax t";

                            $results = $db->query($sql, [])->fetchAll();
                            $options = '';
                            foreach ($results as $result)
                            {
                                $options .= '<option value="' . $result['tax_id'] . '" data-factor="' . $result['factor'] . '">' . $result['factor_percent'] . '</option>';
                            }
                            echo $options;
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-input">
                    <label for="charge_total_display"><?php echo TEXT_INVOICE_CHARGE_TOTAL_VALUE; ?></label>
                    <input type="number" id="charge_total_display" step="0.01" class="calc-trigger">
                </div>
            </div>

            <div class="filter-actions">
                <button type="button" class="action-btn btn-primary" id="saveCharge"><?php echo TEXT_ADD; ?></button>
                <button type="button" class="action-btn btn-outline-danger closeChargePopup" id="cancelCharge"><?php echo TEXT_CANCEL; ?></button>
            </div>
        </div>
    </div>