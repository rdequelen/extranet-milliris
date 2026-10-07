<?php

// Ce depot n'utilise PAS symfony/flex : la liste des bundles et les fichiers de
// config/ sont ecrits a la main et versionnes. C'est volontaire — aucune recette
// ne peut reecrire silencieusement un fichier que l'equipe a ajuste. Contrepartie :
// un `composer require` ulterieur n'auto-configure rien, il faut ajouter la ligne
// ici et le fichier dans config/packages/ soi-meme.
return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    Pentatrion\ViteBundle\PentatrionViteBundle::class => ['all' => true],
    Symfony\UX\StimulusBundle\StimulusBundle::class => ['all' => true],
    Symfony\UX\Turbo\TurboBundle::class => ['all' => true],
    Symfony\UX\TwigComponent\TwigComponentBundle::class => ['all' => true],
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => ['dev' => true, 'test' => true],
];
