import json
import sys
import requests
from scipy.optimize import linprog


# Read input data from the temporary file
if len(sys.argv) > 1:
    temp_file = sys.argv[1]
    with open(temp_file, 'r') as file:
        input_data = file.read()
    try:
        data = json.loads(input_data)
    except json.JSONDecodeError as e:
        sys.exit(1)

    min_calories = float(data['min_calories'])
    min_protein = float(data['min_protein'])
    min_fat = float(data['min_fat'])
    min_acids = float(data['min_acids'])
    min_carbs = float(data['min_carbs'])
    min_salt = float(data['min_salt'])
    min_sugar = float(data['min_sugar'])
    products = data['products']
    current_menu = data['currentMenu']  # Get currentMenu

    # Prepare data for linear programming
    c = []  # Coefficients for the objective function (prices)
    A = []  # Coefficients for the inequality constraints
    b = []  # Right-hand side values for the inequality constraints

    # Constraints: min_calories, min_fat, min_acids, min_carbs, min_salt, min_sugar
    A.append([float(product['calories']) for product in products.values()])
    b.append(min_calories)
    A.append([float(product['protein']) for product in products.values()])
    b.append(min_protein)
    A.append([float(product['fat']) for product in products.values()])
    b.append(min_fat)
    A.append([float(product['acids']) for product in products.values()])
    b.append(min_acids)
    A.append([float(product['carbs']) for product in products.values()])
    b.append(min_carbs)
    A.append([float(product['salt']) for product in products.values()])
    b.append(min_salt)
    A.append([float(product['sugar']) for product in products.values()])
    b.append(min_sugar)

    # Objective function: minimize total price
    # Convert A and b to negative for the inequality constraints (Ax >= b -> -Ax <= -b)
    A = [[-a for a in row] for row in A]
    b = [-val for val in b]
    c = [product['price'] for product in products.values()]

    # Bounds for each variable (quantity of each product)
    x_bounds = [
        (
            product['quantity'] if product.get('is_minimum', 0) == 1 else 0, 
            product['max_quantity'] if product['max_quantity'] is not None else float('inf')
        ) 
        for product in products.values()
    ]

    # Solve the linear programming problem using the Simplex method
    res = linprog(c, A_ub=A, b_ub=b, bounds=x_bounds, method='highs')
    if res.success:
        optimized_quantities = res.x
        optimized_products = []
        for i, product_id in enumerate(products.keys()):
            optimized_products.append({
                'id': product_id,
                'quantity': round(optimized_quantities[i], 2)
            })

        # Include currentMenu in the data sent to PHP script
        data_to_send = {
            'currentMenu': current_menu,
            'optimized_products': optimized_products,
            'success': True
        }

        # Redirect to menu.php with success parameters
        print(f"Location: ../menu.php?calories={round(min_calories)}&protein={round(min_protein)}&fat={round(min_fat)}&fatacids={round(min_acids)}&carb={round(min_carbs)}&salt={round(min_salt)}&sugar={round(min_sugar)}#result_section")
    else:
        data_to_send = {
            'success': False,
            'message': res.message
        }
        # Redirect to menu.php with failure message
        print("Location: ../menu.php?optimization_failed=true#result_section")

    # Send optimized data to PHP file
    url = 'http://localhost/Projektesanas_labratorija/Functions/update_quantities.php'
    headers = {'Content-Type': 'application/json'}
    try:
        response = requests.post(url, headers=headers, data=json.dumps(data_to_send))
        response.raise_for_status()  # Raise an error for bad status codes
    except requests.exceptions.RequestException as e:
        print(f"Error sending data to PHP file: {e}")
