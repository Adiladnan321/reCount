<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Calculation</title>
    <style>
        table {
            width: 50%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        input {
            width: 100%;
            box-sizing: border-box;
            text-align: center;
        }
    </style>
</head>
<body>

<table>
    <thead>
        <tr>
            <th>Value 1</th>
            <th>Value 2</th>
            <th>Product</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><input type="number" class="value1" value="5"></td>
            <td><input type="number" class="value2" value="10"></td>
            <td><input type="text" class="product" readonly></td>
        </tr>
        <tr>
            <td><input type="number" class="value1" value="3"></td>
            <td><input type="number" class="value2" value="7"></td>
            <td><input type="text" class="product" readonly></td>
        </tr>
        <tr>
            <td><input type="number" class="value1" value="6"></td>
            <td><input type="number" class="value2" value="4"></td>
            <td><input type="text" class="product" readonly></td>
        </tr>
    </tbody>
</table>

<script>
    function calculateProduct(row) {
        const value1 = parseFloat(row.querySelector('.value1').value) || 0;
        const value2 = parseFloat(row.querySelector('.value2').value) || 0;
        const productCell = row.querySelector('.product');
        productCell.value = value1 * value2;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach(row => {
            calculateProduct(row);
            row.querySelector('.value1').addEventListener('input', () => calculateProduct(row));
            row.querySelector('.value2').addEventListener('input', () => calculateProduct(row));
        });
    });
</script>

</body>
</html>
