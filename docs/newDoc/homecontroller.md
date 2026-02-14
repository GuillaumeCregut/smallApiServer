## HomeController

### Description

`HomeController` is the default controller handling the root route (`/`). It demonstrates CRUD operations and common controller patterns.

### Location
`App\Controllers\HomeController`

### Inheritance

Extends `AbstractController` and inherits all base functionality.

### Constructor

```php
public function __construct()
{
    parent::__construct();
}
```

Calls parent constructor which initializes the `Request` singleton.

### Methods

#### `getDatas(): ResponseInterface`

Handles `GET` requests to retrieve data.

**HTTP Method:** `GET`
**Route:** Root (`/`)
**Query Parameters:** 
- `id` (optional): Retrieve specific data item

**Behavior:**

- If `id` parameter present: Retrieves single data item from database
- If `id` parameter absent: Retrieves all data items from database
- Returns JSON response with status 200

**Example Requests:**

```bash
# Get all data
GET / HTTP/1.1

# Get specific data (ID=5)
GET /?id=5 HTTP/1.1
```

**Response:**

```json
// All data
{
    "data": [
        {"id": 1, "name": "Item 1"},
        {"id": 2, "name": "Item 2"}
    ]
}

// Single data
{
    "id": 5,
    "name": "Item 5"
}
```

#### `addData(): ResponseInterface`

Handles `POST` requests to create new data.

**HTTP Method:** `POST`
**Route:** Root (`/`)
**Status Code:** 201 Created

**Validation:**
1. Extracts request data using `$this->request->getAllDatas()`
2. Validates incoming data
3. Returns `422 Unprocessable Entity` if validation fails
4. Returns `404 Not Found` if error occurs

**Behavior:**

- Validates input data
- Inserts new entity into database
- Returns newly created entity as JSON

**Example Request:**

```bash
POST / HTTP/1.1
Content-Type: application/json

{
    "name": "New Item",
    "description": "Item description"
}
```

**Response:**

```json
HTTP/1.1 201 Created
Content-Type: application/json

{
    "id": 3,
    "name": "New Item",
    "description": "Item description"
}
```

#### `changeData(): ResponseInterface`

Handles `PUT` and `PATCH` requests to update data.

**HTTP Methods:** `PUT`, `PATCH`
**Route:** Root (`/`)
**Status Code:** 204 No Content or response data

**Behavior:**

- Extracts request data
- Validates incoming data
- Returns `422 Unprocessable Entity` if validation fails
- Updates database record
- Returns 204 (no content) or updated entity

**Validation:**
Similar to `addData()`, validates all incoming data before updating.

**Example Request:**

```bash
PUT / HTTP/1.1
Content-Type: application/json

{
    "id": 1,
    "name": "Updated Item",
    "description": "Updated description"
}
```

**Response:**

```
HTTP/1.1 204 No Content
```

#### `deleteData(): ResponseInterface`

Handles `DELETE` requests to remove data.

**HTTP Method:** `DELETE`
**Route:** Root (`/`)
**Authentication Required:** Yes
**Status Code:** 204 No Content

**Behavior:**

1. First checks user authentication: `if (!$this->isUserAuth())`
2. Returns `401 Unauthorized` if user not authenticated
3. Deletes entity from database
4. Returns 204 (no content)

**Authentication Check:**

```php
if (!$this->isUserAuth()) {
    return $this->returnError(401);
}
```

Only authenticated users can delete data.

**Example Request:**

```bash
DELETE / HTTP/1.1
Authorization: Bearer <jwt_token>

{
    "id": 1
}
```

**Response:**

```
HTTP/1.1 204 No Content
```

**Unauthorized Response:**

```
HTTP/1.1 401 Unauthorized
```

### Request-Response Flow for HomeController

```
Client Request
    ↓
Router determines HomeController
    ↓
Kernel instantiates HomeController
    ↓
Kernel dispatches lifecycle events
    ↓
Kernel calls appropriate method (based on HTTP verb):
    - GET → getDatas()
    - POST → addData()
    - PUT/PATCH → changeData()
    - DELETE → deleteData()
    ↓
Method processes request, queries database
    ↓
Method returns ResponseInterface (JsonResponse or ClientErrorResponse)
    ↓
Response sent to client
```

