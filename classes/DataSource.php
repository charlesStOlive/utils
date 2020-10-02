<?php namespace Waka\Utils\Classes;

// use Lang;
// use MathPHP\Algebra;
// use MathPHP\Functions\Map;*
use ApplicationException;
use Config;
use October\Rain\Support\Collection;
use Yaml;

class DataSource
{
    use \Waka\Utils\Classes\Traits\StringRelation;
    use \Waka\Cloudis\Classes\Traits\CloudisKey;

    public $name;
    public $id;
    public $class;
    private $config;
    public $relations;
    public $otherRelations;
    public $emails;
    public $editFunctions;
    public $aggFunctions;
    public $modelId;
    public $model;
    public $testId;
    public $modelName;

    public function __construct($id = null, $type_id = "name")
    {
        $globalConfig = new Collection($this->getSrConfig());
        $config = $globalConfig->where($type_id, $id)->first();
        $this->class = $config['class'] ?? false;
        trace_log($id);
        trace_log($type_id);
        if (!$this->class) {
            throw new ApplicationException('Erreur data source model');
        }
        $this->config = $config;
        $this->id = $config['id'];
        //
        $this->relations = $config['relations'] ?? null;
        $this->otherRelations = $config['otherRelations'] ?? false;
        //
        $this->emails = $config['emails'] ?? false;
        $this->name = $config['name'] ?? false;
        //
        $this->class = $config['class'] ?? false;
        //
        $this->testId = $config['test_id'] ?? false;
        //
        $this->editFunctions = $config['editFunctions'] ?? null;
        $this->aggFunctions = $config['aggFunctions'] ?? false;

        $config = null;
    }

    public function instanciateModel($id = null)
    {
        if ($id) {
            $this->model = $this->class::find($id);

        } else if ($this->testId) {
            $this->model = $this->class::find($this->testId);
        } else {
            $this->model = $this->class::first();
        }
        $this->modelName = $this->model;
    }
    public function getModel($modelId)
    {
        $this->instanciateModel($modelId);
        return $this->model;
    }

    /**
     * TRAVAIL SUR LES PRODUCTOR
     */

    public function getPartialOptions($modelId = null, $productorModel)
    {

        $documents = $productorModel::where('data_source_id', $this->id);
        $this->instanciateModel($modelId);

        $optionsList = [];

        foreach ($documents->get() as $document) {
            if ($document->is_scope) {
                //Si il y a des limites
                $scope = new \Waka\Utils\Classes\Scopes($document, $this->model);
                if ($scope->checkScopes()) {
                    $optionsList[$document->id] = $document->name;
                }
            } else {
                $optionsList[$document->id] = $document->name;
            }
        }
        return $optionsList;
    }

    public function getPartialIndexOptions($productorModel)
    {

        $documents = $productorModel::where('data_source_id', $this->id);

        $optionsList = [];

        foreach ($documents->get() as $document) {
            if ($document->is_scope) {
                //Si il y a des limites
                $scope = new \Waka\Utils\Classes\Scopes($document);
                if ($scope->checkIndexScopes()) {
                    $optionsList[$document->id] = $document->name;
                }
            } else {
                $optionsList[$document->id] = $document->name;
            }
        }
        return $optionsList;
    }

    public function getKeyAndEmbed()
    {
        if (!$this->relations) {
            return null;
        }
        $array = array_keys($this->relations);

        foreach ($this->relations as $key => $relation) {
            if ($relation['embed'] ?? false) {
                foreach ($relation['embed'] as $subRelation) {
                    array_push($array, $key . '.' . $subRelation);
                }
            }
        }
        return $array;

    }

    /**
     * RECUPERATION DES VALEURS DES MODELES ET DE LEURS LIAISON
     */

    public function getModels($modelId = null)
    {
        $this->instanciateModel($modelId);

        $constructApi = null;
        $embedRelation = $this->getKeyAndEmbed();
        if ($embedRelation) {
            $constructApi = $this->class::with($embedRelation)->find($this->model->id);
        } else {
            $constructApi = $this->class::find($this->model->id);
        }
        return $constructApi;
    }

    public function getOtherRelationValues()
    {
        if (!$this->otherRelations) {
            return [];
        }
        $otherRelation = [];
        foreach ($this->otherRelations as $key => $otherRelation) {
            $class = new $otherRelation['class'];
            $class = $class::first()->toArray();
            $otherRelation[$key] = $class;
        }
        return $otherRelation;
    }

    public function getValues($modelId = null, $withInde = true)
    {
        $dsApi = array_merge($this->getModels($modelId)->toArray(), $this->getOtherRelationValues());
        return $dsApi;
    }

    public function getDotedValues($modelId = null)
    {
        $constructApi = $this->getValues($modelId);
        $api[snake_case($this->name)] = $constructApi;
        return array_dot($api);
    }
    public function getSimpleDotedValues($modelId = null)
    {
        $constructApi = $this->getValues($modelId);
        return array_dot($constructApi);
    }

    /**
     * FONCTIONS DE RECUPERATION DES IMAGES
     * les fonctions utulisent le trait CloudisKey
     */
    public function getAllPicturesKey($modelId = null)
    {
        $this->instanciateModel($modelId);
        $collection = $this->getAllDataSourceImage();
        if ($collection) {
            return $collection->lists('name', 'key');
        } else {
            return null;
        }
    }

    public function getOnePictureKey($key, $modelId = null)
    {
        $this->instanciateModel($modelId);
        $collection = $this->getAllDataSourceImage();
        return $collection->where('key', $key)->first();
    }

    private function getAllDataSourceImage()
    {
        $allImages = new Collection();
        $listsImages = null;
        $listMontages = null;

        //si il y a le trait cloudi dans la classe il y a des images à chercher
        if (method_exists($this->model, 'getCloudisList')) {
            $listsImages = $this->model->getCloudisList();
            $listMontages = $this->model->getCloudiMontagesList();
        }

        if ($listsImages) {
            $allImages = $allImages->merge($listsImages);
        }
        if ($listMontages) {
            $allImages = $allImages->merge($listMontages);
        }
        $relationWithImages = new Collection($this->relations);
        if ($relationWithImages->count()) {
            $relationWithImages = $relationWithImages->where('image', true)->keys();
            foreach ($relationWithImages as $relation) {
                $subModel = $this->getStringModelRelation($this->model, $relation);
                $listsImages = $subModel->getCloudisList($relation);
                $listMontages = $subModel->getCloudiMontagesList($relation);
                if ($listsImages) {
                    $allImages = $allImages->merge($listsImages);
                }
                if ($listMontages) {
                    $allImages = $allImages->merge($listMontages);
                }
            }
        }

        return $allImages;
    }

    public function getPicturesUrl($modelId, $dataImages)
    {
        if (!$dataImages) {
            return;
        }

        $this->instanciateModel($modelId);
        $allPictures = [];
        // trace_log("--dataImages--");
        // trace_log($dataImages);
        foreach ($dataImages as $image) {
            //trace_log($image);
            //On recherche le bon model
            $modelImage = $this->model;
            $img;

            if ($image['relation'] != 'self') {
                $modelImage = $this->getStringModelRelation($this->model, $image['relation']);
            }
            //trace_log("nom du model " . $modelImage->name);

            $options = [
                'width' => $image['width'] ?? null,
                'height' => $image['height'] ?? null,
                'crop' => $image['crop'] ?? null,
                'gravity' => $image['gravity'] ?? null,
            ];

            // si cloudi ( voir GroupedImage )
            if ($image['type'] == 'cloudi') {
                $img = $modelImage->{$image['field']};
                if ($img) {
                    $img = $img->getUrl($options);
                } else {
                    $img = \Cloudder::secureShow(CloudisSettings::get('srcPath'));
                }
                // trace_log('image cloudi---' . $img);
            }
            // si montage ( voir GroupedImage )
            if ($image['type'] == 'montage') {
                $montage = $modelImage->montages->find($image['id']);
                $img = $modelImage->getCloudiModelUrl($montage, $options);
                // trace_log('montage ---' . $img);
            }
            $allPictures[$image['code']] = [
                'path' => $img,
                'width' => $options['width'],
                'height' => $options['height'],
            ];

        }
        return $allPictures;
    }

    /**
     * Utils for EMAIL ---------------------------------------------------
     * Fonctions d'identifications des contacts, utilises dans les popup de wakamail
     * getstringrelation est dans le trait StringRelation
     */
    public function getContact($type, $modelId = null)
    {
        $this->instanciateModel($modelId);
        $emailData = $this->emails[$type] ?? null;
        if (!$emailData) {
            throw new \ApplicationException("Les contacts ne sont pas correctement configurés.");
        }

        // trace_log("getContact emaildata | ");
        // trace_log($emailData);
        // trace_log($this->emails);
        // trace_log($type);

        if (!$emailData) {
            return;
        }
        $relation = $emailData['relation'] ?? null;
        $contacts;
        if ($relation) {
            $contacts = $this->getStringRelation($this->model, $relation);
        } else {
            $contacts = $this->model;
        }

        // trace_log($this->model->name);
        // trace_log($emailData['relations']);

        // trace_log("liste des relations pour contact");
        // trace_log($contacts->toArray());
        // trace_log($contacts['name']);
        // trace_log(get_class($contacts));

        $results = [];

        if (!$contacts) {
            return;
        }
        //On cherche si on a un l'email via la key
        $email = $contacts[$emailData['key']] ?? false;

        if ($email) {
            array_push($results, $email);
        } else {
            foreach ($contacts as $contact) {
                $email = $contact[$emailData['key']] ?? false;
                if ($email) {
                    array_push($results, $email);
                }
            }
        }
        trace_log($results);
        return $results;

    }

    /**
     * UTILS FOR FUNCTIONS ------------------------------------------------------------
     */

    /**
     * retourne la liste des fonctions dans la classe de fonction liée à se data source.
     * Utiise par le formwifget functionlist et les wakamail, datasource, aggregator
     */
    public function getFunctionsList()
    {
        if (!$this->editFunctions) {
            throw new \ApplicationException("Il manque le chemin de la classe fonction dans DataSource pour ce model");
        }
        $fn = new $this->editFunctions;
        return $fn->getFunctionsList();
    }
    public function getFunctionsOutput($fnc)
    {
        if (!$this->editFunctions) {
            throw new \ApplicationException("Il manque le chemin de la classe fonction dans DataSource pour ce model");
        }
        $fn = new $this->editFunctions;
        return $fn->getFunctionsOutput($fnc);
    }
    /**
     * retourne simplement le function class. mis en fonction pour ajouter l'application exeption sans nuire à la lisibitilé de la fonction getFunctionsCollections
     */
    public function getFunctionClass()
    {
        if (!$this->editFunctions) {
            return null;
        }
        return new $this->editFunctions;
    }
    /**
     * Retourne les valeurs d'une fonction du model de se datasource.
     * templatemodel = wakamail ou document ou aggregator
     * id est l'id du model de datasource
     */

    public function getFunctionsCollections($modelId, $model_functions)
    {
        if (!$model_functions) {
            return;
        }
        $this->instanciateModel($modelId);

        $collection = [];
        $fnc = $this->getFunctionClass();
        $fnc->setModel($this->model);

        foreach ($model_functions as $item) {
            $itemFnc = $item['functionCode'];
            $collection[$item['collectionCode']] = $fnc->{$itemFnc}($item);
        }
        return $collection;
    }

    /**
     * GLOBAL
     */

    public function getSrConfig()
    {
        $dataSource = Config::get('waka.crsm::data_source.src');
        trace_log($dataSource);
        if ($dataSource) {
            return Yaml::parseFile(plugins_path() . $dataSource);
        } else {
            return Yaml::parseFile(plugins_path() . '/waka/crsm/config/datasources.yaml');
        }

    }

    public function getControllerUrlAttribute()
    {
        return strtolower($this->getConfigValue('author') . '\\'
            . $this->getConfigValue('plugin') . '\\'
            . $this->getConfigValue('controller'));
    }

    public function getConfigValue($key)
    {
        return $this->config[$key];
    }

}
