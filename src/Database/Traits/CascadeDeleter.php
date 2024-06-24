<?php

namespace FimPablo\SigExtenders\Database\Traits;

trait CascadeDeleter
{
    public function deleteCascade()
    {
        foreach ($this->cascadeDeletable ?? [] as $relation) {
            $this->{$relation}()->each(function ($relatedModel) {
                $relatedModel->delete();
            });
        }

        $this->delete();
    }
}
