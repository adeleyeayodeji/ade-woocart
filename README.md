# ade-woocart-no-auth

# Description

This plugin helps you to create a cart for your WooCommerce store through API.

# Installation

1. Upload the plugin files to the `/wp-content/plugins/ade-woocart-no-auth` directory, or install the plugin through the WordPress plugins screen directly.

2. Activate the plugin through the 'Plugins' screen in WordPress

3. Use the Settings->Ade Woocart screen to configure the plugin

## How to use this plugin?

1. Add a cart
   - Endpoint: `http://yourdomain.com/wp-json/ade-woocart-no-auth/v1/cart`
   - Method: `POST`
   - Body:
   ```json
   {
     "product_id": 248,
     "quantity": 3,
     "user_id": 1
   }
   ```
   - Response:
   ```json
   [
     {
       "key": "b53b3a3d6ab90ce0268229151c9bde11",
       "product_id": 55,
       "product_name": "test",
       "product_price": "500",
       "product_image": "http://localhost:8888/wordpress2/wp-content/uploads/2022/09/dartcourse-150x150.png",
       "quantity": 3
     },
     {
       "key": "3cec07e9ba5f5bb252d13f5f431e4bbb",
       "product_id": 247,
       "product_name": "Product 2",
       "product_price": "4000",
       "product_image": "http://localhost:8888/wordpress2/wp-content/uploads/2022/10/306309967_205687448454197_8999579521544198312_n-150x150.jpg",
       "quantity": 1
     },
     {
       "key": "621bf66ddb7c962aa0d22ac97d69b793",
       "product_id": 248,
       "product_name": "Product 3",
       "product_price": "3000",
       "product_image": "http://localhost:8888/wordpress2/wp-content/uploads/2022/11/20221015_075005-150x150.jpg",
       "quantity": 3
     }
   ]
   ```
2. Get cart

   - Endpoint: `http://yourdomain.com/wp-json/ade-woocart-no-auth/v1/cart`
   - Method: `GET`
   - Body:

   ```json
   {
     "user_id": 1
   }
   ```

   - Response:

   ```json
   [
     {
       "key": "b53b3a3d6ab90ce0268229151c9bde11",
       "product_id": 55,
       "product_name": "test",
       "product_price": "500",
       "product_image": "http://localhost:8888/wordpress2/wp-content/uploads/2022/09/dartcourse-150x150.png",
       "quantity": 3
     },
     {
       "key": "3cec07e9ba5f5bb252d13f5f431e4bbb",
       "product_id": 247,
       "product_name": "Product 2",
       "product_price": "4000",
       "product_image": "http://localhost:8888/wordpress2/wp-content/uploads/2022/10/306309967_205687448454197_8999579521544198312_n-150x150.jpg",
       "quantity": 1
     }
   ]
   ```

3. Delete a cart
   - Endpoint: `http://yourdomain.com/wp-json/ade-woocart-no-auth/v1/cart`
   - Method: `DELETE`
   - Body:
   ```json
   {
     "user_id": 1,
     "cart_key": "3cec07e9ba5f5bb252d13f5f431e4bbb"
   }
   ```
   - Response:
   ```json
   {
     "message": "Item removed from cart",
     "cart": [
       {
         "key": "621bf66ddb7c962aa0d22ac97d69b793",
         "product_id": 248,
         "product_name": "Product 3",
         "product_price": "3000",
         "product_image": "http://localhost:8888/wordpress2/wp-content/uploads/2022/11/20221015_075005-150x150.jpg",
         "quantity": 3
       },
       {
         "key": "b53b3a3d6ab90ce0268229151c9bde11",
         "product_id": 55,
         "product_name": "test",
         "product_price": "500",
         "product_image": "http://localhost:8888/wordpress2/wp-content/uploads/2022/09/dartcourse-150x150.png",
         "quantity": 1
       }
     ]
   }
   ```
