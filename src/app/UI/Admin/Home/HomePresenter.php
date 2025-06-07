<?php

declare(strict_types=1);

namespace App\UI\Admin\Home;

use App\Component\Datagrid\DataGrid;
use App\Component\Datagrid\DataGridFactory;
use App\Component\Log\LogActionEnum;
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
use Google\Analytics\Data\V1beta\Row;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Nette;
use Nette\Database\Table\ActiveRow;

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

    protected function createComponentGridLog(): DataGrid
    {
        return $this->dataGridFactory->create($this->logModel->getToGrid(), $this->logDataGridEntityFactory->create());
    }

    public function actionDefault(): void
    {
        $this->template->recentActivities = $this->logModel->getRecent(15);

        $serviceAccount = $this->settingModel->getDefault()?->google_service_account;
        $gaServiceId = $this->settingModel->getDefault()?->ga_service_id;

        if($serviceAccount === null || $gaServiceId === null){
            $this->template->notConfigured = true;
        }else {
            $this->template->notConfigured = false;

            $dateFrom = new Nette\Utils\DateTime()->modify('-1 week');

            putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $this->parameterBag->uploadDir . '/' . $serviceAccount->saved_name);
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
            $data[$date->format('Y-m-d')] = new GaGraphItem(clone $date, 0);

            /** @var Row $row */
            foreach ($rows as $row) {
                $googleDate = Nette\Utils\DateTime::createFromFormat('Ymd', $row->getDimensionValues()[0]->getValue());
                $data[$googleDate->format('Y-m-d')]->count = (int)$row->getMetricValues()[0]->getValue();
            }
            $this->template->dataAccessGraph = $data;
        }
    }

    public function getActivityIcon(ActiveRow $log): string
    {
        try {
            $action = LogActionEnum::from($log['action']);
        } catch (\ValueError $e) {
            return 'ki-filled ki-information';
        }
        
        return match($action) {
            LogActionEnum::Created => 'ki-filled ki-plus',
            LogActionEnum::Updated => 'ki-filled ki-pencil',
            LogActionEnum::Deleted => 'ki-filled ki-trash',
            LogActionEnum::Imported => 'ki-filled ki-entrance-left',
            LogActionEnum::DeletedUnused => 'ki-filled ki-trash',
            LogActionEnum::ChangeDefault => 'ki-filled ki-star',
            LogActionEnum::ChangeActive => 'ki-filled ki-toggle-on',
            LogActionEnum::ChangePassword => 'ki-filled ki-key',
            LogActionEnum::SetAuthorization => 'ki-filled ki-security-user',
            LogActionEnum::TestEmail => 'ki-filled ki-sms',
            LogActionEnum::Translate => 'ki-filled ki-language',
            LogActionEnum::Synchronize => 'ki-filled ki-arrows-circle',
            LogActionEnum::CreatedItem => 'ki-filled ki-plus',
            LogActionEnum::UpdatedItem => 'ki-filled ki-pencil',
            LogActionEnum::DeletedItem => 'ki-filled ki-trash',
            LogActionEnum::CreatedGroup => 'ki-filled ki-people',
            LogActionEnum::UpdatedGroup => 'ki-filled ki-pencil',
            LogActionEnum::DeletedGroup => 'ki-filled ki-trash',
            default => 'ki-filled ki-information',
        };
    }

    public function getActivityDescription(ActiveRow $log): string
    {
        try {
            $action = LogActionEnum::from($log['action']);
            
            try {
                $user = $log->ref('user', 'user_id');
                $userName = $user && isset($user->name) ? $user->name : 'Systém';
            } catch (\Exception $e) {
                $userName = 'Systém';
            }
            
            $table = $log['table'] ?? 'neznámý modul';
            
            return match($action) {
                LogActionEnum::Created => sprintf('%s vytvořil nový záznam v modulu %s', $userName, $table),
                LogActionEnum::Updated => sprintf('%s upravil záznam v modulu %s', $userName, $table),
                LogActionEnum::Deleted => sprintf('%s smazal záznam v modulu %s', $userName, $table),
                LogActionEnum::Imported => sprintf('%s importoval data do modulu %s', $userName, $table),
                LogActionEnum::DeletedUnused => sprintf('%s smazal nepoužívané záznamy v modulu %s', $userName, $table),
                LogActionEnum::ChangeDefault => sprintf('%s změnil výchozí nastavení v modulu %s', $userName, $table),
                LogActionEnum::ChangeActive => sprintf('%s změnil aktivní stav v modulu %s', $userName, $table),
                LogActionEnum::ChangePassword => sprintf('%s změnil heslo v modulu %s', $userName, $table),
                LogActionEnum::SetAuthorization => sprintf('%s nastavil autorizaci v modulu %s', $userName, $table),
                LogActionEnum::TestEmail => sprintf('%s otestoval email v modulu %s', $userName, $table),
                LogActionEnum::Translate => sprintf('%s přeložil obsah v modulu %s', $userName, $table),
                LogActionEnum::Synchronize => sprintf('%s synchronizoval data v modulu %s', $userName, $table),
                LogActionEnum::CreatedItem => sprintf('%s vytvořil položku v modulu %s', $userName, $table),
                LogActionEnum::UpdatedItem => sprintf('%s upravil položku v modulu %s', $userName, $table),
                LogActionEnum::DeletedItem => sprintf('%s smazal položku v modulu %s', $userName, $table),
                LogActionEnum::CreatedGroup => sprintf('%s vytvořil skupinu v modulu %s', $userName, $table),
                LogActionEnum::UpdatedGroup => sprintf('%s upravil skupinu v modulu %s', $userName, $table),
                LogActionEnum::DeletedGroup => sprintf('%s smazal skupinu v modulu %s', $userName, $table),
                default => sprintf('%s - %s v modulu %s', $userName, $action->translate($this->translator), $table),
            };
        } catch (\Exception $e) {
            return 'Neznámá aktivita';
        }
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();
        
        $this->template->addFilter('getActivityIcon', [$this, 'getActivityIcon']);
        $this->template->addFilter('getActivityDescription', [$this, 'getActivityDescription']);
        $this->template->addFilter('timeAgo', function($date) {
            try {
                if (!$date instanceof \DateTime) {
                    $date = new \DateTime($date);
                }
                
                $now = new \DateTime();
                $diff = $now->diff($date);
                
                if ($diff->days > 0) {
                    return sprintf('před %d dny', $diff->days);
                } elseif ($diff->h > 0) {
                    return sprintf('před %d hodinami', $diff->h);
                } else {
                    return sprintf('před %d minutami', max(1, $diff->i));
                }
            } catch (\Exception $e) {
                return 'neznámé datum';
            }
        });
    }
}
