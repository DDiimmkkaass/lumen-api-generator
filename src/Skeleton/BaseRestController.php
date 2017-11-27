<?php

namespace DDiimmkkaass\Api\Skeleton;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

abstract class BaseRestController extends BaseController
{
    
    /**
     * Eloquent model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model;
     */
    protected $model;
    
    /**
     * Constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
        
        $this->model = $this->model();
    }
    
    /**
     * Eloquent model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    abstract protected function model();
    
    /**
     * Display a listing of the resource.
     * GET /api/{resource}.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $with = $this->getEagerLoad();
        $skip = (int) $this->request->input('skip', 0);
        $limit = $this->calculateLimit();
        
        $items = $limit
            ? $this->model->with($with)->skip($skip)->limit($limit)->get()
            : $this->model->with($with)->get();
        
        return $this->respondWithCollection($items, $skip, $limit);
    }
    
    /**
     * Store a newly created resource in storage.
     * POST /api/{resource}.
     *
     * @return JsonResponse
     */
    public function store()
    {
        $data = $this->request->json()->get($this->resourceKeySingular);
        
        if (!$data) {
            return $this->errorWrongArgs('Empty data');
        }
        
        $validator = Validator::make($data, $this->rulesForCreate());
        if ($validator->fails()) {
            return $this->errorValidation($validator->messages()->getMessages());
        }
        
        $this->unguardIfNeeded();
        
        $item = $this->model->create($data);
        
        return $this->respondWithItem($item);
    }
    
    /**
     * Display the specified resource.
     * GET /api/{resource}/{id}.
     *
     * @param int $id
     *
     * @return JsonResponse
     */
    public function show($id)
    {
        $with = $this->getEagerLoad();
        
        $item = $this->findItem($id, $with);
        if (!$item) {
            return $this->errorNotFound();
        }
        
        return $this->respondWithItem($item);
    }
    
    /**
     * Update the specified resource in storage.
     * PUT /api/{resource}/{id}.
     *
     * @param int $id
     *
     * @return JsonResponse
     */
    public function update($id)
    {
        $data = $this->request->json()->get($this->resourceKeySingular);
        
        if (!$data) {
            return $this->errorWrongArgs('Empty data');
        }
        
        $item = $this->findItem($id);
        if (!$item) {
            return $this->errorNotFound();
        }
        
        $validator = Validator::make($data, $this->rulesForUpdate($item->id));
        if ($validator->fails()) {
            return $this->errorValidation($validator->messages()->getMessages());
        }
        
        $this->unguardIfNeeded();
        
        $item->fill($data);
        $item->save();
        
        return $this->respondWithItem($item);
    }
    
    /**
     * Remove the specified resource from storage.
     * DELETE /api/{resource}/{id}.
     *
     * @param int $id
     *
     * @return JsonResponse
     */
    public function destroy($id)
    {
        $item = $this->findItem($id);
        
        if (!$item) {
            return $this->errorNotFound();
        }
        
        $item->delete();
        
        return response()->json(['message' => 'Deleted']);
    }
    
    /**
     * Show the form for creating the specified resource.
     *
     * @return JsonResponse
     */
    public function create()
    {
        return $this->errorNotImplemented();
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return JsonResponse
     */
    public function edit($id)
    {
        return $this->errorNotImplemented();
    }
    
    /**
     * Get item according to mode.
     *
     * @param int   $id
     * @param array $with
     *
     * @return mixed
     */
    protected function findItem($id, array $with = [])
    {
        if ($this->request->has('use_as_id')) {
            return $this->model->with($with)->where($this->request->input('use_as_id'), '=', $id)->first();
        }
        
        return $this->model->with($with)->find($id);
    }
}