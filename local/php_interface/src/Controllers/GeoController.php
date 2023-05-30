<?php

use Local\Exception\GeoException;
use Local\Manager\GeoManager;
use Local\Response\ErrorResponse;
use Local\Response\SuccessResponse;
use Local\Service\GeoService;
use OpenApi\Annotations as OA;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Respect\Validation\Validator as V;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class GeoController
{
    /**
     * @var mixed
     */
    private $validator;
    /**
     * @var GeoService
     */
    private GeoService $geoService;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container)
    {
        $this->validator = $container->get('validator');
        $this->geoService = new GeoService();
    }

    /**
     * @OA\Post(
     *     tags={"Геолокация"},
     *     path="/api/geo/setCity",
     *     summary="установка текущего города",
     *     @OA\RequestBody(
     *         description="id города",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="city", type="int", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     * @throws GeoException
     */
    public function setCity(Request $request, Response $response, array $args = [])
    {
        $this->validator->validate($request, [
            'city' => [
                'rules' => V::notBlank(),
                'messages' => [
                    'blank' => 'Поле city обязательное',
                ]
            ]
        ]);

        if ($this->validator->isValid()) {
            $id = $request->getParsedBody()['city'];
            try {
                $this->geoService->changeCity($id);
                return new SuccessResponse();
            } catch (GeoException $e) {
                return new ErrorResponse($e->getMessage());
            }
        }
        return new ErrorResponse($this->validator->getErrors());
    }

    /**
     * @OA\Post(
     *     tags={"Геолокация"},
     *     path="/api/geo/findCities",
     *     summary="поиск городов по запросу",
     *     @OA\RequestBody(
     *         description="поисковый запрос",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="search_input", type="string", example="Моск")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     * @throws GeoException
     * @throws ErrorException
     */
    public function findCities(Request $request, Response $response, array $args = [])
    {
        $this->validator->validate($request, [
            'search_input' => [
                'rules' => V::notBlank(),
                'messages' => [
                    'blank' => 'Поисковый запрос пустой',
                ]
            ]
        ]);

        if ($this->validator->isValid()) {
            $name = $request->getParsedBody()['search_input'];
            try {
                $result = $this->geoService->findCities($name);
                return new SuccessResponse($result);
            } catch (GeoException $e) {
                return new ErrorResponse($e->getMessage());
            }
        }
        return new ErrorResponse($this->validator->getErrors());
    }
}