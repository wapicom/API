# **The API**

Warehouse API is a service that provides easy and understandable working with warehouses and courier services throughout the Europe.

In this document you will find the technical information regarding API work and methods.

## API access
API base URLs are: 

>Test environment: `https://warehouse-api-test.azurewebsites.net/api`

>Production environment: `https://warehouse-api.azurewebsites.net/api`

The endpoint is protected by the **authentication token**. The personalizes token is provided to each client via 
`x-client-id` HTTP header. Production and test environments has different tokens and hash keys. 

Also a login and password for the test and production environment required. They are different for test and production environments.

Virtual products for test requests must be "Demo Product-1" and "Demo Product-2". Also it is important to indicate in the request the country of delivery "ES" or "IT". 


### Message signing
 For the message signing the HMAC-SHA1 is used
- Hash key (e.g. HMAC secret) will be provided personally
- A **signature** should be indicated in the `x-signature` HTTP header

# Creating a new outbound request
To create a new request (order) you need following details:

>`POST /outbounds`

**Headers:**

```text
accept: application/json
content-type: application/json
x-client-id: <a guid, provided specially for you>
x-signature: <a HMAC signature you get using `HMAC secret`, provided specially for you>
```
**Body**:

```js 
{  
    "orderNumber":"5", //Each order id should be unique
    "product":
    {  
	    "name":"Product Name",
	    "quantity":2,
	    "price":0.0,
	    "unitPriceForInvoice": "54.90"
    },
    "additionalProducts":
    [
        {
		"name":"Product Name-2",
		"quantity":2,
		"price":0.0, 
		"UnitPriceForInvoice":27.72
        },
	{
		"name":"296041002",
		"quantity":1,
		"price":0.0, 
		"UnitPriceForInvoice":33.12
	}
    ],
    "cashOnDelivery":85.00,
    "receiver":
    {  
	    "firstName":"receiver name",
	    "lastName":"receiver surname",
	    "phoneNumber":"123456789",
	    "emailAddress":"test@test.com",
	    "nationalID":"XXXXXXXXXX",
	    "houseNumber":"122",
	    "addressText":"Some street",
	    "addressAdditionalInfo":"apt. 35",
	    "city":"Venice",
	    "country":"IT",
	    "zipCode":"30123"
    },
    "comment":"some text",
    "whCode":"ITWH6",
    "whCodeAsMandatory":"true",
    "ReasonForExport":"sale",
    "DiscountFlatAmount":0.00,
    "ShippingFlatAmount":0.00,
    "TaxFlatAmount":3.98,
    "CurrencyForInvoice":"EUR",
    "checkBeforeCOD":true,
    "pipelineId":"983584"
}
```
 **Important!**

- For prepaid orders fill following fields `product.price`, `cashOnDelivery` and `additionalProducts.price` with `0` 
- All fields are required, except `additionalProducts`, `receiver.lastName`, `receiver.emailAddress`, `receiver.houseNumber`, `receiver.nationalID`, `whCode`, `whCodeAsMandatory`, `ReasonForExport`, `DiscountFlatAmount`, `ShippingFlatAmount`, `TaxFlatAmount`, `CurrencyForInvoice`, `checkBeforeCOD`, `pipelineId`.
- `cashOnDelivery` field is added later with purpose to override calculations of COD, based on `price` field of products. That is, when COD amount is specified in `cashOnDelivery` field and **is greater then zero**, amounts in `price` are ignored.   
- `additionalProducts` field is optional. When you need to send more than one product, first product you need to pass via `product` node and other products in `additionalProducts` array.
- `nationalID` field is used to be included in invoices, which are required in some cases (e.g. sending orders to Spanish islands).
- `whCode` field is optional. If this parameter is passed, then the system will try to send the order to this warehouse. But if there are no leftovers, then to the one where there is.
- `whCodeAsMandatory` field is connected with `whCode` field. This parameter must be set to true if the order should be sent only from the warehouse specified in the `whCode` parameter. If `whCodeAsMandatory` = False, then the system will try to send an order from another warehouse if there is no item in the warehouse specified in `whCode`.
- `checkBeforeCOD` field is optional and should be passed only for orders to Bulgaria. This parameter is passed when the package needs to be opened before the client will pay for it.

**Response:**
 
**200 OK** 

Status means that order has been created. In response you are getting the Tracking number of the order registered in WAPI system

```json
{
    "trackingNumber": "WH0000000024"
}
```

_Any status except 200 OK should be considered as an error:_

**401 Unauthorized** 

 `x-client-id` is missing or is wrong

**400 Bad Request**  

Signature or request data is not valid

**500 Internal Server Error** 

 For internal errors
```
{
    "Error": "<SOME ERROR DESCRIPTION>"
}
```
## Preferred Delivery Date and Time parameters
In the Create Order request now can be indicated the preferred Delivery Date and Time in the following format:
 

```js
{
   ...
   "preferredDeliveryDate": "YYYY-MM-DD",
   "preferredDeliveryDayPart": "morning" | "evening"
   ...
}
```
 **Important!**

- Both fields are optional. If the date is indicated in incorrect format, or if the `preferredDeliveryDayPart` will have any other value than `morning` or `evening` these values will be ignored. 

- If in order there is `preferredDeliveryDate` indicated then the order will be sent to the partner not less than 2 days before that date, so that delivery does not happen before the correct date.

 ##  Tracking orders
 
To track the order you should know the tracking (WH) number of the order and should send the below mentioned API request:

> `POST /tracking`

**Headers:**
```text
accept: application/json
content-type: application/json
x-client-id: <a guid, provided specially for you>
x-signature: <a HMAC signature you get using `HMAC secret`, provided specially for you>
```
**Body:**
```js
{  
    "trackingNumbers":[  
        "WH0000000024",
        ...
    ]
}
```

**Response:**

 **200 OK**

```json
{
    "statuses": [
        {
            "trackingNumber": "WH0000000024",
            "deliveryStatus": "Pending",
            "deliveryStatusText": "Order is being processed",
            "troubleStatus": "IsAbsent"
        }
    ]
}
```
_Any status except 200 OK should be considered as an error._

**401 Unauthorized** - when x-client-id is missing or is wrong

**400 Bad Request** - when signature or request data is not valid

**500 Internal Server Error** - for internal errors
```
{
    "Error": "<SOME ERROR DESCRIPTION>"
}
```

### Possible statuses:
- `Pending` - the status indicates that the outbound order has been just created and still not passed to the Partner;
- `Error` - the status appears when during the order's processing happens a technical error. Status may appear only before the order has been sent for delivery;
- `WaybillNotCreated` - the status means that there are some questions regarding the delivery address. This is the temporary status, which after solving all address related questions will be changed to the status InTransit;
- `AssignedToPartner` - the status means that the order has been passed to our partners in the destination country for processing;
- `Dispatched` - the status indicates that the outbound order has been passed to the carrier services;
- `InTransit` - the status indicates that the order, is on it's way to the customer;
- `Delivered` - parcel has been delivered to the customer;
- `Returning` - the order is on it's way back to the initial warehouse;
- `ReturnedToSender` - indicates that products was returned to the initial warehouse and remainders are added back to the stock;
- `Cancelled` - parcel's processing is cancelled;
- `OutOfStock` - order cannot be processed, because of lack of goods in the warehouse. It will be processed right after goods are present on stock (delivered new or returned);
- `Lost` - due to some reasons, partners may report this status;
- `Damaged` - the status may occur if the parcel has been damaged on the way to the customer.
###Possible trouble statuses (appear when order has some problems with delivery):
- `IsAbsent1` - indicates that a recipient was not at the delivery address during first delivery attempt;
- `IsAbsent` - indicates that a recipient was not at the delivery address during following delivery attempts;
- `CannotLocateConsignee1` - indicates that a courier is not able to find where to deliver the package during first delivery attempt;
- `CannotLocateConsignee` - indicates that a courier is not able to find where to deliver the package during following delivery attempts;
- `IsRefused` - indicates that a recipient refused to receive the package (pay for it).

 **Important!**

- Please note, that you should sign up request as you send it. That is, that payloads `{"TrackingNumbers":["12346126348721"]}` and `{"trackingNumbers": [ "12346126348721" ]}` will have different hash signatures.
The same belongs to line endings and tab spaces in a payload.

# Getting Product's Remained Amount
This operation allows you to get the stock remainders of the concrete product, or on the selected warehouse, or country. 
> `POST /stock/getProductRemainder`

**Headers:**
```text
accept: application/json
content-type: application/json
x-client-id: <a guid, provided specially for you>
x-signature: <a HMAC signature you get using `HMAC secret`, provided specially for you>
```
**Body:**
```json 
{"country":"Italy", "productName": "Demo Product-1"}
```

**Response:**

**200 OK**

```json
{
    "country": "Italy",
    "productName": "Demo Product-1",
    "reminderQuantity": -398
}
```

Remarks:
- Negative reminders may occure in test environment. In production it should always be positive or zero.
- `country` value may be passed as a country name (Italy) or a country code (IT)
- if `productName` is not passed, you can get reminders for all products in specified country
- if `country` and `productName` are not passed, you can get reminders for all products in all countries


_Any status except 200 OK should be considered as an error_

**401 Unauthorized** - when x-client-id is missing or is wrong

**400 Bad Request** - when signature or request data is not valid

**500 Internal Server Error** - for internal errors
```
{
    "Error": "<SOME ERROR DESCRIPTION>"
}
```
# Getting Product Remainded Amount v.2
This changed version of getting remainder request helps client to obtain detailed information regarding his remained goods amount. You can find out what is the remaining on the warehouse quantity, what amount of goods is reserved, what is available at the moment, what amount is in transit right now and what amount is returning to the warehouse.
The second version of the API for getting remained amount is accessible via:

>`POST /stock/getProductRemainderV2`

The request structure is the same as in the first version:

**Headers:**
```text
accept: application/json
content-type: application/json
x-client-id: <a guid, provided specially for you>
x-signature: <a HMAC signature you get using `HMAC secret`, provided specially for you>
```
**Body:**
```json 
{"country":"Italy", "productName": "Demo Product-1"}
```
 
**Response:**

**200 OK**

```json
{
    "country": "Italy",
    "warehouseName" : "ITWH1",
    "productName": "Demo Product-1",
    "remainingQuantity":0,
    "reservedQuantity":0,
    "availableQuantity":0, 
    "InTransitQuantity":0, 
    "returningQuantity":0 
}
```
# Update An Order
To change order's data you should send the request to the:

> `POST /outbounds/UpdateOrder`

Can be used the same request as is used for creating a new order, but additionally the `TrackingNumber` field should be added with the value (WH tracking number) of the particular order that you want to change.

**Headers:**
```text
accept: application/json
content-type: application/json
x-client-id: <a guid, provided specially for you>
x-signature: <a HMAC signature you get using `HMAC secret`, provided specially for you>
```
**Body:**
```js
{  
    "trackingNumber": "WH00000012345",  // Enter here the tracking number of the order you want to change
    "orderNumber":"5",
    "product":
    {  
	    "name":"Product Name",
	    "quantity":2,
	    "price":41.5
    },
    "additionalProducts" :
    [
    ],
    "cashOnDelivery": 85.00,
    "receiver":
    {  
	    "firstName":"receiver name",
	    "lastName":"receiver surname",
	    "phoneNumber":"123456789",
	    "emailAddress":"test@test.com",
	    "nationalID":"XXXXXXXXXX",
	    "houseNumber":"122",
	    "addressText":"Some street",
	    "addressAdditionalInfo":"apt. 35",
	    "city":"Venice",
	    "country":"IT",
	    "zipCode":"30123"
    },
    "comment":"some text (optional)"
}
```

 **Please Note:**

Order can be changed only if it is in one of these statuses: `Pending`, `Error`.

**Response:**
 
**200 OK**


```
{
       "The order with number 'WH0000261906' has been updated successfully"
}
```


If the order already is in status which doesnt allow to change order details you will receive the following response:

```
{
	"error": "This order cannot be edited anymore!"
}
```

Also order can not be changed if it has a critical open incident. In this case the response is:


```
{
	"error": "This order has critical incident opened. Please resolve incident before order update."
}
```

If in the change request were sent the same data as order has had initially, you will get the response:


```
{
	"error": "The data you have provided brings no changes. Please check and try again!"
}
```
