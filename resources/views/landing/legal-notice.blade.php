@extends('layouts.landing')

@section('content')
    <section class="bg-gray-50 dark:bg-gray-900">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 sm:px-10 py-8 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-skin-base/10 to-transparent">
                    <p class="text-sm font-semibold tracking-wide uppercase text-skin-base">Kulatable</p>
                    <h1 class="mt-2 text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white">Mentions legales</h1>
                    <p class="mt-3 text-sm sm:text-base text-gray-600 dark:text-gray-300">
                        Informations legales et reglementaires relatives a la plateforme Kulatable.
                    </p>
                </div>

                <article class="px-6 sm:px-10 py-8 space-y-10 text-gray-700 dark:text-gray-300 leading-relaxed">
                    <section class="space-y-4">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Editeur du site</h2>
                        <p>
                            Le site Kulatable est edite par une societe specialisee dans le developpement et l'exploitation
                            de solutions numeriques destinees a la gestion et a la transformation digitale des etablissements
                            de restauration.
                        </p>
                        <p>
                            La societe exploitant la plateforme est enregistree conformement aux dispositions legales
                            applicables en Republique Democratique du Congo et exerce ses activites dans le respect des
                            reglementations en vigueur relatives au commerce electronique, aux services numeriques et a la
                            protection des donnees.
                        </p>
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-5">
                            <dl class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Raison sociale</dt>
                                    <dd class="mt-1 font-semibold text-gray-900 dark:text-white">ILLUMINATION METAVERSE GROUP</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Statut juridique</dt>
                                    <dd class="mt-1 font-semibold text-gray-900 dark:text-white">SARLU</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Adresse du siege social</dt>
                                    <dd class="mt-1">195 Kabambare, DR Congo</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ville</dt>
                                    <dd class="mt-1">Kinshasa, Republique Democratique du Congo</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Adresse electronique</dt>
                                    <dd class="mt-1">
                                        <a href="mailto:contact@revival-business.com" class="text-skin-base hover:underline">
                                            contact@revival-business.com
                                        </a>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Telephone</dt>
                                    <dd class="mt-1">
                                        <a href="tel:+243860275282" class="text-skin-base hover:underline">+243 860 275 282</a>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </section>

                    <section class="space-y-4">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Hebergement du site</h2>
                        <p>
                            Le site Kulatable est heberge par un prestataire technique specialise dans l'hebergement securise
                            de plateformes numeriques.
                        </p>
                        <p>L'hebergeur assure :</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>la disponibilite du service</li>
                            <li>la securisation des donnees</li>
                            <li>la continuite de l'infrastructure technique</li>
                        </ul>
                        <p>
                            Les coordonnees completes de l'hebergeur peuvent etre communiquees sur demande pour toute
                            necessite legale ou administrative.
                        </p>
                    </section>

                    <section class="space-y-4">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Objet du site</h2>
                        <p>
                            Le site Kulatable a pour objet de presenter et de fournir une plateforme numerique permettant aux
                            restaurants et etablissements de restauration de gerer efficacement leurs activites.
                        </p>
                        <p>La plateforme offre notamment des fonctionnalites liees a :</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>la gestion des tables et des commandes</li>
                            <li>la gestion des menus et produits</li>
                            <li>l'organisation des services</li>
                            <li>la gestion des paiements</li>
                            <li>l'analyse des performances commerciales</li>
                        </ul>
                    </section>

                    <section class="space-y-4">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Propriete intellectuelle</h2>
                        <p>L'ensemble des contenus presents sur le site Kulatable, incluant notamment :</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>les textes</li>
                            <li>les elements graphiques</li>
                            <li>les logos</li>
                            <li>les illustrations</li>
                            <li>les videos</li>
                            <li>les logiciels</li>
                            <li>l'architecture du site</li>
                        </ul>
                        <p>
                            Ces elements sont proteges par les lois relatives a la propriete intellectuelle et aux droits
                            d'auteur.
                        </p>
                        <p>
                            Toute reproduction, representation, modification, publication, adaptation ou exploitation, totale ou
                            partielle, de ces elements est strictement interdite sans autorisation prealable ecrite de
                            l'editeur.
                        </p>
                        <p>
                            Toute utilisation non autorisee peut entrainer des poursuites conformement aux dispositions legales
                            applicables.
                        </p>
                    </section>

                    <section class="space-y-4">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Responsabilite</h2>
                        <p>
                            Kulatable met tout en oeuvre afin d'assurer l'exactitude et la mise a jour des informations
                            publiees sur son site.
                        </p>
                        <p>
                            Cependant, l'editeur ne peut garantir l'exactitude, la completude ou l'actualite permanente des
                            informations diffusees.
                        </p>
                        <p>
                            En consequence, l'utilisateur reconnait utiliser ces informations sous sa responsabilite exclusive.
                        </p>
                        <p>Kulatable ne saurait etre tenu responsable :</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>des interruptions du service</li>
                            <li>des dysfonctionnements techniques independants de sa volonte</li>
                            <li>des dommages directs ou indirects pouvant resulter de l'utilisation du site</li>
                        </ul>
                    </section>

                    <section class="space-y-4">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Protection des donnees</h2>
                        <p>
                            Kulatable attache une importance particuliere a la protection des donnees personnelles des
                            utilisateurs.
                        </p>
                        <p>Les informations collectees via la plateforme sont utilisees uniquement dans le cadre :</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>de la gestion des comptes utilisateurs</li>
                            <li>de l'amelioration des services</li>
                            <li>du fonctionnement technique de la plateforme</li>
                        </ul>
                        <p>
                            Ces donnees sont traitees conformement aux principes de confidentialite et de securite applicables
                            aux services numeriques.
                        </p>
                        <p>
                            Les utilisateurs disposent d'un droit d'acces, de rectification et de suppression de leurs donnees
                            personnelles conformement aux reglementations applicables.
                        </p>
                    </section>

                    <section class="space-y-4">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Droit applicable</h2>
                        <p>
                            Les presentes mentions legales sont regies par le droit applicable en Republique Democratique du
                            Congo.
                        </p>
                        <p>
                            Tout litige relatif a l'utilisation du site Kulatable sera soumis a la competence des juridictions
                            territorialement competentes en Republique Democratique du Congo, sauf disposition legale contraire.
                        </p>
                    </section>

                    <section class="space-y-4">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Contact</h2>
                        <p>
                            Pour toute question relative aux presentes mentions legales ou au fonctionnement du site, les
                            utilisateurs peuvent contacter l'editeur via la section Contact du site Kulatable.
                        </p>
                    </section>
                </article>
            </div>
        </div>
    </section>
@endsection
