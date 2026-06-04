<?php

namespace Devture\Bundle\NagiosBundle\Controller\Api;

use Devture\Bundle\NagiosBundle\ApiModelBridge\CommandBridge;
use Devture\Bundle\NagiosBundle\Model\Command;
use Devture\Bundle\NagiosBundle\Repository\CommandRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class CommandApiController extends AbstractController
{
    public function __construct(
        private readonly CommandRepository $repository,
        private readonly CommandBridge $commandBridge,
    ) {
    }

    #[Route(
        '/commands/{type}',
        name: 'devture_nagios.api.command.list',
        methods: ['GET'],
        requirements: ['type' => 'serviceCheck|serviceNotification|__TYPE__'],
    )]
    public function list(string $type): JsonResponse
    {
        // __TYPE__ is the placeholder the Angular component registry substitutes
        // at call time; treat it as the default (service-check) commands.
        if ($type === '__TYPE__') {
            $type = Command::TYPE_SERVICE_CHECK;
        }

        $commands = $this->repository->findAllByType($type);

        $result = array_map(fn (Command $command) => $this->commandBridge->export($command), $commands);

        return $this->json($result);
    }
}
