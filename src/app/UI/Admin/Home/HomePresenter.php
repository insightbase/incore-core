<?php

declare(strict_types=1);

namespace App\UI\Admin\Home;

use App\Component\Datagrid\DataGrid;
use App\Component\Datagrid\DataGridFactory;
use App\Model\Admin\Log;
use App\Model\Admin\Setting;
use App\UI\Accessory\Admin\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\Admin\PresenterTrait\StandardTemplateTrait;
use App\UI\Accessory\ParameterBag;
use App\UI\Admin\Home\DataGrid\LogDataGridEntityFactory;
use App\UI\Admin\Home\GaGraph\GaGraphItem;
use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Nette;

/**
 * @property-read Template $template
 */
final class HomePresenter extends Nette\Application\UI\Presenter
{
    use StandardTemplateTrait;
    use RequireLoggedUserTrait;

    public function __construct(
        private readonly Log                      $logModel,
        private readonly LogDataGridEntityFactory $logDataGridEntityFactory,
        private readonly DataGridFactory          $dataGridFactory,
        private readonly ParameterBag             $parameterBag,
        private readonly Setting                  $settingModel,
    )
    {
        parent::__construct();
    }

    protected  function createComponentGridLog():DataGrid{
        return $this->dataGridFactory->create($this->logModel->getToGrid(), $this->logDataGridEntityFactory->create());
    }

    public function actionDefault():void
    {
        $serviceAccount = $this->settingModel->getDefault()->google_service_account;
        $gaServiceId = $this->settingModel->getDefault()->ga_service_id;

        if($serviceAccount === null || $gaServiceId === null){
            $this->template->notConfigured = true;
        }else {
            $this->template->notConfigured = false;

            $dateFrom = new Nette\Utils\DateTime()->modify('-1 week');

            putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $this->parameterBag->uploadDir . '/' . $serviceAccount->saved_name); // Cesta k JSON klíči
            $client = new BetaAnalyticsDataClient();

            $request = (new RunReportRequest())
                ->setProperty('properties/' . $gaServiceId)
                ->setDateRanges([
                    new DateRange([
                        'start_date' => $dateFrom->format('Y-m-d'),
                        'end_date' => 'today',
                    ]),
                ])
                ->setDimensions([new Dimension([
                    'name' => 'date',
                ]),
                ])
                ->setMetrics([new Metric([
                    'name' => 'activeUsers',
                ])
                ]);
            $response = $client->runReport($request);
            $rows = $response->getRows();

            $data = [];
            $date = clone $dateFrom;
            while($date->format('j.n.Y') !== new Nette\Utils\DateTime()->format('j.n.Y')){
                $data[$date->format('Y-m-d')] = new GaGraphItem(clone $date, 0);
                $date->modify('+1 day');
            }

            foreach ($rows as $row) {
                $data[$row->getDimensions()[0]] = $row->getMetrics()[0]->getValue();
            }
            $this->template->dataAccessGraph = $data;
        }
    }
}
