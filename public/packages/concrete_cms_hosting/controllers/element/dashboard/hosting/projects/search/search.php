<?php /** @noinspection DuplicatedCode */

namespace Concrete\Package\ConcreteCmsHosting\Controller\Element\Dashboard\Hosting\Projects\Search;

use Concrete\Core\Controller\ElementController;
use Concrete\Core\Entity\Search\Query;
use Concrete\Core\Foundation\Serializer\JsonSerializer;

class Search extends ElementController
{

    protected $headerSearchAction;
    protected $query;

    public function getElement()
    {
        return 'dashboard/hosting/projects/search/search';
    }

    public function setQuery(Query $query = null): void
    {
        $this->query = $query;
    }

    public function setHeaderSearchAction(string $headerSearchAction): void
    {
        $this->headerSearchAction = $headerSearchAction;
    }

    public function view()
    {
        $this->set('form', $this->app->make('helper/form'));
        $this->set('token', $this->app->make('token'));

        if (isset($this->headerSearchAction)) {
            $this->set('headerSearchAction', $this->headerSearchAction);
        } else {
            $this->set('headerSearchAction', $this->app->make('url')->to('/dashboard/hosting/projects'));
        }

        if (isset($this->query)) {
            $this->set('query', $this->app->make(JsonSerializer::class)->serialize($this->query, 'json'));
        }
    }

}
