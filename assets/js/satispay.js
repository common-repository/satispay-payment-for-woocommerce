'use strict';
var satispayPay = angular.module("satispayPay", ['ngRoute']);
satispayPay.controller("Basket", function ($scope, $http, $interval, $timeout, $location) {
	var absUrl = $location.absUrl();
	var urlSplitted = absUrl.split("/");
	$scope.merchantcode = urlSplitted[urlSplitted.length-1];

	$scope.keypressHandler = function (event) {
		if (event.keyCode == 13) {
			$timeout(function () {
				if (event.keyCode == 13) {
					jQuery("button[type=\"submit\"]").focus();
				}
			}, 0, false);
		}
	}

	$scope.onKeyDel = function (event) {
		if (event.keyCode == 8) {
			if (!$scope.form.decimal.$viewValue){
				jQuery("#decimal").fadeOut(5, function(){
					jQuery("#integer").focus();
				});
			}
		}
	}
});// JavaScript Document
	