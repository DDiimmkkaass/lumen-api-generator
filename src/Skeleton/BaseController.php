<?php

namespace DDiimmkkaass\Api\Skeleton;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Laravel\Lumen\Routing\Controller as LumenController;
use League\Fractal\Manager;
use League\Fractal\Pagination\Cursor;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\DataArraySerializer;

abstract class BaseController extends LumenController
{
    /**
     * HTTP header status code.
     *
     * @var int
     */
    protected $statusCode = 200;

    /**
     * Fractal Manager instance.
     *
     * @var Manager
     */
    protected $fractal;

    /**
     * Fractal Transformer instance.
     *
     * @var \League\Fractal\TransformerAbstract
     */
    protected $transformer;

    /**
     * Illuminate\Http\Request instance.
     *
     * @var Request
     */
    protected $request;

    /**
     * Do we need to unguard the model before create/update?
     *
     * @var bool
     */
    protected $unguard = false;

    /**
     * Number of items displayed at once if not specified.
     * There is no limit if it is 0 or false.
     *
     * @var int|bool
     */
    protected $defaultLimit = false;

    /**
     * Maximum limit that can be set via $_GET['limit'].
     *
     * @var int|bool
     */
    protected $maximumLimit = false;

    /**
     * Resource key for an item.
     *
     * @var string
     */
    protected $resourceKeySingular = 'data';

    /**
     * Resource key for a collection.
     *
     * @var string
     */
    protected $resourceKeyPlural = 'data';

    /**
     * Constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->transformer = $this->transformer();

        $this->fractal = new Manager();
        $this->fractal->setSerializer($this->serializer());

        $this->request = $request;

        if ($this->request->has('include')) {
            $this->fractal->parseIncludes(camel_case($this->request->input('include')));
        }
    }

    /**
     * Transformer for the current model.
     *
     * @return \League\Fractal\TransformerAbstract
     */
    abstract protected function transformer();

    /**
     * Serializer for the current model.
     *
     * @return \League\Fractal\Serializer\SerializerAbstract
     */
    protected function serializer()
    {
        return new DataArraySerializer();
    }

    /**
     * Getter for statusCode.
     *
     * @return int
     */
    protected function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Setter for statusCode.
     *
     * @param int $statusCode Value to set
     *
     * @return self
     */
    protected function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Respond with a given item.
     *
     * @param                                          $item
     * @param \League\Fractal\TransformerAbstract|null $transformer
     * @param string|null                              $resourceKeySingular
     *
     * @return JsonResponse
     */
    protected function respondWithItem($item, $transformer = null, $resourceKeySingular = null)
    {
        $resource = new Item(
            $item,
            $transformer ? : $this->transformer,
            $resourceKeySingular ? : $this->resourceKeySingular
        );

        $rootScope = $this->prepareRootScope($resource);

        return $this->respondWithArray($rootScope->toArray());
    }

    /**
     * Respond with a given collection.
     *
     * @param                                          $collection
     * @param int                                      $skip
     * @param int                                      $limit
     * @param \League\Fractal\TransformerAbstract|null $transformer
     * @param string|null                              $resourceKeyPlural
     *
     * @return JsonResponse
     */
    protected function respondWithCollection(
        $collection,
        $skip = 0,
        $limit = 0,
        $transformer = null,
        $resourceKeyPlural = null
    )
    {
        $resource = new Collection(
            $collection,
            $transformer ? : $this->transformer,
            $resourceKeyPlural ? : $this->resourceKeyPlural);

        if ($limit) {
            $cursor = new Cursor($skip, $skip + $limit, $collection->count());
            $resource->setCursor($cursor);
        }

        $rootScope = $this->prepareRootScope($resource);

        return $this->respondWithArray($rootScope->toArray());
    }

    /**
     * Respond with a given array of items.
     *
     * @param array $array
     * @param array $headers
     *
     * @return JsonResponse
     */
    protected function respondWithArray(array $array, array $headers = [])
    {
        return response()->json($array, $this->statusCode, $headers);
    }

    /**
     * Response with the current error.
     *
     * @param string $message
     *
     * @return mixed
     */
    protected function respondWithError($message)
    {
        return $this->respondWithArray([
            'error' => [
                'http_code' => $this->statusCode,
                'message'   => $message,
            ],
        ]);
    }

    /**
     * Prepare root scope and set some meta information.
     *
     * @param Item|Collection $resource
     *
     * @return \League\Fractal\Scope
     */
    protected function prepareRootScope($resource)
    {
        $resource->setMetaValue('available_includes', $resource->getTransformer()->getAvailableIncludes());
        $resource->setMetaValue('default_includes', $resource->getTransformer()->getDefaultIncludes());
    
        return $this->fractal->createData($resource);
    }

    /**
     * Get the validation rules for create.
     *
     * @return array
     */
    protected function rulesForCreate()
    {
        return [];
    }

    /**
     * Get the validation rules for update.
     *
     * @param int $id
     *
     * @return array
     */
    protected function rulesForUpdate($id)
    {
        return [];
    }

    /**
     * Generate a Response with a 403 HTTP header and a given message.
     *
     * @param $message
     *
     * @return JsonResponse
     */
    protected function errorForbidden($message = 'Forbidden')
    {
        return $this->setStatusCode(403)->respondWithError($message);
    }

    /**
     * Generate a Response with a 500 HTTP header and a given message.
     *
     * @param string $message
     *
     * @return JsonResponse
     */
    protected function errorInternalError($message = 'Internal Error')
    {
        return $this->setStatusCode(500)->respondWithError($message);
    }

    /**
     * Generate a Response with a 404 HTTP header and a given message.
     *
     * @param string $message
     *
     * @return JsonResponse
     */
    protected function errorNotFound($message = 'Resource Not Found')
    {
        return $this->setStatusCode(404)->respondWithError($message);
    }

    /**
     * Generate a Response with a 401 HTTP header and a given message.
     *
     * @param string $message
     *
     * @return JsonResponse
     */
    protected function errorUnauthorized($message = 'Unauthorized')
    {
        return $this->setStatusCode(401)->respondWithError($message);
    }

    /**
     * Generate a Response with a 400 HTTP header and a given message.
     *
     * @param string$message
     *
     * @return JsonResponse
     */
    protected function errorWrongArgs($message = 'Wrong Arguments')
    {
        return $this->setStatusCode(400)->respondWithError($message);
    }
    
    /**
     * Response with validation error.
     *
     * @param array $messages
     *
     * @return JsonResponse
     */
    protected function errorValidation(array $messages = [])
    {
        return $this->setStatusCode(422)->respondWithArray(['errors' => $messages]);
    }
    
    /**
     * Generate a Response with a 501 HTTP header and a given message.
     *
     * @param string $message
     *
     * @return JsonResponse
     */
    protected function errorNotImplemented($message = 'Not implemented')
    {
        return $this->setStatusCode(501)->respondWithError($message);
    }

    /**
     * Specify relations for eager loading.
     *
     * @return array
     */
    protected function getEagerLoad()
    {
        $include = $this->request->input('include', '');
        $includes = explode(',', $include);
        $includes = array_filter($includes);
        
        foreach ($includes as $i => $include) {
            if (strpos($include, ':')) {
                $include = explode(':', $include);
                $include = $include[0];
            }
            
            $includes[$i] = $include;
        }
        
        return $includes ? : [];
    }

    /**
     * Unguard eloquent model if needed.
     */
    protected function unguardIfNeeded()
    {
        if ($this->unguard) {
            $this->model->unguard();
        }
    }

    /**
     * Calculates limit for a number of items displayed in list.
     *
     * @return int
     */
    protected function calculateLimit()
    {
        $limit = (int) $this->request->input('limit', $this->defaultLimit);

        return ($this->maximumLimit && $this->maximumLimit < $limit) ? $this->maximumLimit : $limit;
    }
}