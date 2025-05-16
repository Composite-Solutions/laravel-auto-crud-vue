<?php

declare(strict_types=1);

namespace Composite\LaravelAutoCrud\Services;

use InvalidArgumentException;
use Composite\LaravelAutoCrud\Builders\ControllerBuilder;
use Composite\LaravelAutoCrud\Builders\RepositoryBuilder;
use Composite\LaravelAutoCrud\Builders\RequestBuilder;
use Composite\LaravelAutoCrud\Builders\ResourceBuilder;
use Composite\LaravelAutoCrud\Builders\RouteBuilder;
use Composite\LaravelAutoCrud\Builders\ServiceBuilder;
use Composite\LaravelAutoCrud\Builders\SpatieDataBuilder;
use Composite\LaravelAutoCrud\Builders\ViewBuilder;
use Composite\LaravelAutoCrud\Builders\VueBuilder;

use function Laravel\Prompts\info;

class CRUDGenerator
{
    public function __construct(private ControllerBuilder $controllerBuilder,
        private ResourceBuilder $resourceBuilder,
        private RequestBuilder $requestBuilder,
        private RouteBuilder $routeBuilder,
//        private ViewBuilder $viewBuilder,
        private VueBuilder $vueBuilder,
        private RepositoryBuilder $repositoryBuilder,
        private ServiceBuilder $serviceBuilder,
        private SpatieDataBuilder $spatieDataBuilder)
    {
        $this->controllerBuilder = new ControllerBuilder;
        $this->resourceBuilder = new ResourceBuilder;
        $this->requestBuilder = new RequestBuilder;
        $this->routeBuilder = new RouteBuilder;
//        $this->viewBuilder = new ViewBuilder;
        $this->vueBuilder = new VueBuilder;
        $this->repositoryBuilder = new RepositoryBuilder;
        $this->serviceBuilder = new ServiceBuilder;
        $this->spatieDataBuilder = new SpatieDataBuilder;
    }

    public function generate($modelData, array $options): void
    {
        $checkForType = $options['type'];

        if ($options['pattern'] == 'spatie-data') {
            $spatieDataName = $this->spatieDataBuilder->create($modelData, $options['overwrite']);
        } else {
            $requestName = $this->requestBuilder->create($modelData, $options['overwrite']);
        }

        $repository = $service = null;
        if ($options['repository']) {
            $repository = $this->repositoryBuilder->create($modelData, $options['overwrite']);
            $service = $this->serviceBuilder->create($modelData, $repository, $options['overwrite']);
        }

        $data = [
            'requestName' => $requestName ?? '',
            'repository' => $repository ?? '',
            'service' => $service ?? '',
            'spatieData' => $spatieDataName ?? '',
        ];

        $controllerName = $this->generateController($checkForType, $modelData, $data, $options);
        $this->routeBuilder->create($modelData['modelName'], $controllerName, $checkForType);

        info('Auto CRUD files generated successfully for '.$modelData['modelName'].' Model');
    }

    private function generateController(array $types, array $modelData, array $data, array $options): string
    {
        $controllerName = null;

        if (in_array('api', $types)) {
            $controllerName = $this->generateAPIController($modelData, $data['requestName'], $data['repository'], $data['service'], $options, $data['spatieData']);
        }

        if (in_array('web', $types)) {
            $controllerName = $this->generateWebController($modelData, $data['requestName'], $data['repository'], $data['service'], $options, $data['spatieData']);
        }

        if (! $controllerName) {
            throw new InvalidArgumentException('Unsupported controller type');
        }

        return $controllerName;
    }

    private function generateAPIController(array $modelData, string $requestName, string $repository, string $service, array $options, ?string $spatieData = null): string
    {
        $controllerName = null;

        if ($options['pattern'] == 'spatie-data') {
            $controllerName = $repository
                ? $this->controllerBuilder->createAPIRepositorySpatieData($modelData, $spatieData, $service, $options['overwrite'])
                : $this->controllerBuilder->createAPISpatieData($modelData, $spatieData, $options['overwrite']);
        } elseif ($options['pattern'] == 'normal') {
            $resourceName = $this->resourceBuilder->create($modelData, $options['overwrite']);
            $controllerName = $repository
                ? $this->controllerBuilder->createAPIRepository($modelData, $resourceName, $requestName, $service, $options['overwrite'])
                : $this->controllerBuilder->createAPI($modelData, $resourceName, $requestName, $options['overwrite']);
        }

        if (! $controllerName) {
            throw new InvalidArgumentException('Unsupported controller type');
        }

        return $controllerName;
    }

    private function generateWebController(array $modelData, string $requestName, string $repository, string $service, array $options, string $spatieData = ''): string
    {
        $controllerName = null;

        if ($options['pattern'] == 'spatie-data') {
            $controllerName = $repository
                ? $this->controllerBuilder->createWebRepositorySpatieData($modelData, $spatieData, $service, $options['overwrite'])
                : $this->controllerBuilder->createWebSpatieData($modelData, $spatieData, $options['overwrite']);
        } elseif ($options['pattern'] == 'normal') {
            $controllerName = $repository
                ? $this->controllerBuilder->createWebRepository($modelData, $requestName, $service, $options['overwrite'])
                : $this->controllerBuilder->createWeb($modelData, $requestName, $options['overwrite']);
        }

//        $this->viewBuilder->create($modelData, $options['overwrite']);
        $this->vueBuilder->createIndex($modelData, $requestName, $options['overwrite']);

        if (! $controllerName) {
            throw new InvalidArgumentException('Unsupported controller type');
        }

        return $controllerName;
    }
}
