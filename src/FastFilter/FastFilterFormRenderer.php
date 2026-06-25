<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\FastFilter;

use Contao\Database;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\StringUtil;

final class FastFilterFormRenderer
{
    public function __construct(
        private readonly FastFilterFormService $formService,
    ) {
    }

    public function generateAndInsertFilterForms(string $content, string $template): string
    {
        if (!FastFilterRuntime::isActive()) {
            return $content;
        }

        preg_match_all('/##filterFormPlaceholder::(.*)##/siU', $content, $matches);

        foreach ($matches[1] as $moduleId) {
            $module = Database::getInstance()->prepare("
                SELECT      *
                FROM        `tl_module`
                WHERE       `id` = ?
            ")->execute($moduleId);

            if ($module->numRows !== 1) {
                continue;
            }

            $module->first();
            $formHtml = $this->generateFilterFormHtml($module);
            $content = preg_replace('/##filterFormPlaceholder::' . $moduleId . '##/', $formHtml, $content);
        }

        return $content;
    }

    private function generateFilterFormHtml(object $module): string
    {
        $template = new FrontendTemplate('template_fastFilterForm_default');
        $viewModel = $this->buildViewModelForCurrentRequest();
        $headline = StringUtil::deserialize($module->headline);

        $template->request = Environment::get('request');
        $template->headline = is_array($headline) ? $headline['value'] : $headline;
        $template->hl = is_array($headline) ? $headline['unit'] : 'h1';
        $template->fields = $viewModel['fields'];
        $template->activeCount = $viewModel['activeCount'];
        $template->hasOptions = $viewModel['hasOptions'];
        $template->fastFilterAutoSubmit = !empty($GLOBALS['merconis_globals']['ls_shop_fastFilterAutoSubmit']);
        $template->fastFilterHideZeroMatches = !empty($GLOBALS['merconis_globals']['ls_shop_fastFilterHideZeroMatches']);
        $template->blnNothingToFilter = !$viewModel['hasOptions']
            || (
                !empty($GLOBALS['merconis_globals']['ls_shop_hideFilterFormInProductDetails'])
                && Input::get('product')
            );

        return $template->parse();
    }

    /**
     * @return array{fields: array<int, array<string, mixed>>, activeCount: int, hasOptions: bool}
     */
    private function buildViewModelForCurrentRequest(): array
    {
        if (empty($GLOBALS['merconis_globals'][FastFilterSearchCoordinator::REQUEST_MARKER])) {
            return [
                'fields' => [],
                'activeCount' => 0,
                'hasOptions' => false,
            ];
        }

        return $this->formService->buildViewModel();
    }
}
