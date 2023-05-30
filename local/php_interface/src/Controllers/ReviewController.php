<?php

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Local\Exception\ReviewException;
use Local\Response\ErrorResponse;
use Local\Response\SuccessResponse;
use Local\Service\ReviewService;
use Psr\Container\ContainerInterface;
use Respect\Validation\Validator as V;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class ReviewController
{
    /**
     * @var mixed
     */
    private $validator;
    /**
     * @var ReviewService
     */
    private ReviewService $reviewService;

    public function __construct(ContainerInterface $container)
    {
        $this->validator = $container->get('validator');
        $this->reviewService = new ReviewService();
    }

    /**
     * @OA\Post(
     *     tags={"Отзывы"},
     *     path="/api/review/add",
     *     summary="Добавление отзыва к товару",
     *     @OA\RequestBody(
     *         description="review data",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="productId", type="string", example="1", required=true),
     *                 @OA\Property(property="rating", type="string", example="5", required=true),
     *                 @OA\Property(property="lastName", type="string", example="Константинов", required=true),
     *                 @OA\Property(property="name", type="string", example="Константин", required=true),
     *                 @OA\Property(property="middleName", type="string", example="Константинович", required=true),
     *                 @OA\Property(property="email", type="string", example="konstantinov@test.ru", required=true),
     *                 @OA\Property(property="text1", type="string", example="Достоинства"),
     *                 @OA\Property(property="text2", type="string", example="Недостатки"),
     *                 @OA\Property(property="comment", type="string", example="Комментарий"),
     *                 @OA\Property(property="file", type="array",
     *                     @OA\Items(
     *                         @OA\Property(type="file", example="file.jpg"),
     *                     )
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     * @throws ReviewException
     */
    public function add(Request $request, Response $response, array $args = [])
    {
        $this->validator->validate($request, [
            'email' => [
                'rules' => V::notBlank()->email(),
                'messages' => [
                    'blank' => 'Поле email обязательное',
                ]
            ],
            'productId' => [
                'rules' => V::notBlank(),
                'messages' => [
                    'blank' => 'Поле productId обязательное',
                ]
            ],
            'rating' => [
                'rules' => V::notBlank(),
                'messages' => [
                    'blank' => 'Поле rating обязательное',
                ]
            ],
            'lastName' => [
                'rules' => V::notBlank(),
                'messages' => [
                    'blank' => 'Поле Фамилия обязательное',
                ]
            ],
            'name' => [
                'rules' => V::notBlank(),
                'messages' => [
                    'blank' => 'Поле Имя обязательное',
                ]
            ],
            'middleName' => [
                'rules' => V::notBlank(),
                'messages' => [
                    'blank' => 'Поле Отчество обязательное',
                ]
            ],
        ]);

        if ($this->validator->isValid()) {
            $fields = $request->getParsedBody();
            $fields['file'] = $request->getUploadedFiles()['file'];
            $this->reviewService->add($fields);
            return new SuccessResponse();
        }
        return new ErrorResponse($this->validator->getErrors());
    }

    /**
     * @OA\Post(
     *     tags={"Отзывы"},
     *     path="/api/review/like",
     *     summary="Добавление лайка отзыву",
     *     @OA\RequestBody(
     *         description="id отзыва",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="id", type="int", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     * @throws ReviewException
     */
    public function like(Request $request, Response $response, array $args = [])
    {
        $this->validator->validate($request, [
            'id' => [
                'rules' => V::notBlank(),
                'messages' => [
                    'blank' => 'Поле id обязательное',
                ]
            ],
        ]);

        if ($this->validator->isValid()) {
            $id = $request->getParsedBody()['id'];
            $this->reviewService->addLike($id);
            return new SuccessResponse();
        }
        return new ErrorResponse($this->validator->getErrors());
    }

    /**
     * @OA\Post(
     *     tags={"Отзывы"},
     *     path="/api/review/dislike",
     *     summary="Добавление дизлайка отзыву",
     *     @OA\RequestBody(
     *         description="id отзыва",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="id", type="int", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     * @throws ReviewException
     */
    public function dislike(Request $request, Response $response, array $args = [])
    {
        $this->validator->validate($request, [
            'id' => [
                'rules' => V::notBlank(),
                'messages' => [
                    'blank' => 'Поле id обязательное',
                ]
            ],
        ]);

        if ($this->validator->isValid()) {
            $id = $request->getParsedBody()['id'];
            $this->reviewService->addDislike($id);
            return new SuccessResponse();
        }
        return new ErrorResponse($this->validator->getErrors());
    }
}