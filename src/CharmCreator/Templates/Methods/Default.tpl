---
name: HTTP method with View output
fields:
  METHOD_NAME:
    name: Name of method
    type: input
  METHOD_ARGS:
    name: Method arguments in PHP-style
    type: input
  METHOD_HTTP:
    name: Request type
    type: choice
    choices: GET, POST, PUT, DELETE
    default: GET
  METHOD_URL:
    name: Relative URL
    type: input
  METHOD_ROUTE:
    name: Route name
    type: input
  METHOD_FILTER:
    name: Route filters (e.g. guard:auth)
    type: input
---
#[Route("METHOD_HTTP", "METHOD_URL", "METHOD_ROUTE", "METHOD_FILTER")]
    public function METHOD_NAME($METHOD_ARGS) : View
    {
        // TODO Implement.

        return View::make('METHOD_ROUTE')->with([

        ]);
    }
